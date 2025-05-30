<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\TontineContribution;
use App\Models\TontineInvitation;
use App\Models\TontineMemberOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NjanguiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tontines = $user->tontines()
            ->with(['members.contributions', 'members.order', 'members.user.profile', 'currentBeneficiary', 'contributions'])
            ->withCount(['members', 'contributions'])
            ->get();

        $invitations = TontineInvitation::where('invited_user_id', $user->id)
            ->where('status', 'pending')
            ->with('tontine.creator')


            ->get();

        return $this->reply(true, 'Tontines retrieved successfully', [
            'tontines' => $tontines,
            'invitations' => $invitations
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'amount' => 'required|numeric|min:1000',
            'deadline' => 'nullable|date',
            'start_date' => 'required|date',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
        ]);

        $tontine = DB::transaction(function () use ($validated) {
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $deadline = $validated['deadline'] ?? match ($validated['frequency']) {
                'daily' => $startDate->copy()->addDay(),
                'weekly' => $startDate->copy()->addWeek(),
                'monthly' => $startDate->copy()->addMonth(),
                'yearly' => $startDate->copy()->addYear(),
                default => $startDate->copy()->addWeek(),
            };

            $tontine = Tontine::create([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'deadline' => $deadline,
                'start_date' => $validated['start_date'],
                'creator_id' => Auth::id(),
                'current_beneficiary_id' => Auth::id(),
                'status' => 'active',
                'frequency' => $validated['frequency'],
            ]);

            // Creator as admin
            $admin = $tontine->members()->create([
                'user_id' => Auth::id(),
                'is_admin' => true,
            ]);
            $getMember = TontineMember::where('user_id', Auth::id())->where('tontine_id', $tontine->id)->first();
            $memberId = $getMember->id; // This gets the auto-incremented ID
            info($admin); // Example usage - log the ID
            info($memberId); // Example usage - log the ID
            $tontine->order()->create([
                'member_id' => $memberId,
                'position' => 1,
                'colleted' => false,
            ]);

            // Add other members
            $position = 2;

            foreach ($validated['members'] as $memberId) {
                if ($memberId == Auth::id()) continue;

                $member = $tontine->members()->create([
                    'user_id' => $memberId,
                    'is_admin' => false,
                ]);

                $tontine->order()->create([
                    'member_id' => $member->id,
                    'position' => $position++,
                    'colleted' => false,
                ]);
            }

            return $tontine;
        });

        return $this->reply(true, 'Tontine created successfully', $tontine->load('members.user'), 201);
    }

    public function updateOrder(Request $request, $tontineId)
    {
        $request->validate([
            'members' => 'required|array',
            'members.*.id' => 'required|exists:tontine_members,id',
            'members.*.position' => 'required|integer|min:1',
            'members.*.colleted' => 'sometimes'
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->members as $memberData) {
                TontineMemberOrder::updateOrCreate(
                    [
                        'tontine_id' => $tontineId,
                        'member_id' => $memberData['id']
                    ],
                    [
                        'position' => $memberData['position'],
                        'colleted' => $memberData['colleted'] ?? false
                    ]
                );
            }

            DB::commit();
            return $this->reply(true, 'Member positions updated successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reply(true, 'Failed to update member positions', 500);
        }
    }


    public function show(Tontine $tontine)
    {


        return $this->reply(true, 'Tontine details retrieved', [
            'tontine' => $tontine->load(['members.user', 'currentBeneficiary']),
            'contributions' => $tontine->contributions()
                ->with(['member.user'])
                ->latest()
                ->get(),
            'stats' => $this->calculateStats($tontine)
        ]);
    }

    public function members(Tontine $tontine)
    {


        return $this->reply(
            true,
            'Members retrieved',
            $tontine->members()->with('user.profile', 'order')->get()
        );
    }

    public function addContribution(Request $request, Tontine $tontine)
    {


        $request->validate([
            'amount' => 'required|numeric|min:100',
            'member_id' => 'required|exists:tontine_members,id,user_id,' . Auth::id()
        ]);

        $contribution = $tontine->contributions()->create([
            'member_id' => $request->member_id,
            'amount' => $request->amount,
            'status' => 'pending',
            'paid_at' => null
        ]);

        return $this->reply(true, 'Contribution added', $contribution, 201);
    }

    public function getContributions($tontineId)
    {
        $tontine = Tontine::findOrFail($tontineId);
        $contributions = $tontine->contributions()->with('user')->get();

        return response()->json([
            'status' => 'success',
            'data' => $contributions
        ]);
    }

    public function markPaid(Tontine $tontine, TontineMember $member)
    {
        try {
            DB::beginTransaction();

            // Check total already contributed
            $totalContributed = $tontine->contributions()
                ->where('member_id', $member->id)
                ->sum('amount');

            // Block further payments if already fully paid
            if ($totalContributed >= $tontine->amount) {
                return $this->reply(false, 'This member has already fully paid their contribution.', null, 400);
            }

            // Calculate remaining amount
            $remaining = $tontine->amount - $totalContributed;

            // Create contribution as pending initially
            $contribution = $tontine->contributions()->create([
                'member_id' => $member->id,
                'amount' => $remaining,
                'status' => 'pending',
                'paid_at' => now(),
            ]);

            // Recalculate total after this contribution
            $newTotal = $totalContributed + $remaining;

            // If fully paid now, update all to "paid"
            if ($newTotal >= $tontine->amount) {
                $tontine->contributions()
                    ->where('member_id', $member->id)
                    ->update(['status' => 'paid']);
            }

            DB::commit();

            return $this->reply(true, 'Contribution processed', $contribution);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reply(false, 'An error occurred while processing the contribution.', $e->getMessage(), 500);
        }
    }



    public function stats(Tontine $tontine)
    {


        return $this->reply(true, 'Statistics retrieved', $this->calculateStats($tontine));
    }

    public function history(Tontine $tontine)
    {


        return $this->reply(
            true,
            'History retrieved',
            $tontine->contributions()
                ->with(['member.user'])
                ->where('status', 'paid')
                ->orderBy('paid_at', 'desc')
                ->get()
        );
    }

    public function invite(Request $request, Tontine $tontine)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // Check for existing invitation or membership
        if ($tontine->invitations()->where('invited_user_id', $request->user_id)->exists()) {
            return $this->reply(false, 'User already invited', null, 409);
        }

        if ($tontine->members()->where('user_id', $request->user_id)->exists()) {
            return $this->reply(false, 'User is already a member', null, 409);
        }

        $invitation = $tontine->invitations()->create([
            'invited_by_id' => Auth::id(),
            'invited_user_id' => $request->user_id,
            'status' => 'pending'
        ]);

        return $this->reply(true, 'Invitation sent', $invitation, 201);
    }

    public function respondToInvitation(Request $request, TontineInvitation $invitation)
    {
        $validated = $request->validate([
            'response' => 'required|in:accept,reject',
        ]);

        // Check if invitation is already processed
        if ($invitation->status !== 'pending') {
            return $this->reply(false, 'Invitation has already been processed', null, 400);
        }

        DB::transaction(function () use ($validated, $invitation) {
            if ($validated['response'] === 'accept') {
                $position = $invitation->tontine->order()->max('position') + 1;

                // Check if user is already a member
                if ($invitation->tontine->members()->where('user_id', Auth::id())->exists()) {
                    throw new \Exception('User is already a member of this tontine');
                }

                $member = $invitation->tontine->members()->create([
                    'user_id' => Auth::id(),
                    'is_admin' => false,
                ]);
                $getMember = TontineMember::where('user_id', Auth::id())->where('tontine_id', $invitation->tontine->id)->first();

                $invitation->tontine->order()->create([
                    'member_id' => $getMember->id,
                    'position' => $position,
                    'colleted' => false,
                ]);
            }

            $invitation->update([
                'status' => $validated['response'] === 'accept' ? 'accepted' : 'rejected',
            ]);
        });

        return $this->reply(true, 'Invitation response processed', $invitation);
    }


    private function calculateStats(Tontine $tontine)
    {
        $totalContributions = $tontine->contributions()
            ->where('status', 'paid')
            ->sum('amount');
        $membersCount = $tontine->members()->count();

        $progress = ($totalContributions / ($tontine->amount * $membersCount)) * 100;

        $paidMembersCount = $tontine->contributions()
            ->where('status', 'paid')
            ->distinct('member_id')
            ->count('member_id');

        return [
            'total_contributions' => $totalContributions,
            'amount' => $tontine->amount,
            'progress' => round($progress, 2),
            'members_count' => $membersCount,
            'paid_members_count' => $paidMembersCount,
            'remaining_amount' => max(0, ($tontine->amount * $membersCount) - $totalContributions),
            'deadline' => $tontine->deadline,
            'start_date' => $tontine->start_date,
            'current_week' => $this->calculateCurrentWeek($tontine)
        ];
    }

    private function calculateCurrentWeek(Tontine $tontine)
    {
        if (!$tontine->start_date) {
            return 'Week no 1'; // Default to week 1 if no start date set
        }

        $now = $tontine->deadline;
        $startDate = $tontine->start_date;

        switch ($tontine->frequency) {
            case 'daily':
                return 'Day no ' . ((int) $startDate->diffInDays($now) + 1);
            case 'weekly':
                return 'Week no ' . ((int) $startDate->diffInWeeks($now) + 1);
            case 'monthly':
                return 'Month no ' . ((int) $startDate->diffInMonths($now) + 1);
            case 'yearly':
                return 'Year no ' . ((int) $startDate->diffInYears($now) + 1);
            default:
                return 'Week no ' . ((int) $startDate->diffInWeeks($now) + 1);
        }
    }
}
