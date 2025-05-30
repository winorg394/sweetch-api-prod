<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CalculateChargesRequest;
use App\Models\User;

class TransactionController extends Controller
{
    public const COUNTRIES_CONFIG = [
        'CM' => [
            'name' => 'Cameroun',
            'code' => 'CM',
            'currency' => 'XAF',
            'flag' => 'https://flagcdn.com/cm.svg',
        ],
        'CI' => [
            'name' => 'Côte d\'Ivoire',
            'code' => 'CI',
            'currency' => 'XOF',
            'flag' => 'https://flagcdn.com/ci.svg',
        ],
        'SN' => [
            'name' => 'Sénégal',
            'code' => 'SN',
            'currency' => 'XOF',
            'flag' => 'https://flagcdn.com/sn.svg',
        ],
        'ML' => [
            'name' => 'Mali',
            'code' => 'ML',
            'currency' => 'XOF',
            'flag' => 'https://flagcdn.com/ml.svg',
        ],
        'BF' => [
            'name' => 'Burkina Faso',
            'code' => 'BF',
            'currency' => 'XOF',
            'flag' => 'https://flagcdn.com/bf.svg',
        ],
        'GH' => [
            'name' => 'Ghana',
            'code' => 'GH',
            'currency' => 'GHS',
            'flag' => 'https://flagcdn.com/gh.svg',
        ],
        'NG' => [
            'name' => 'Nigeria',
            'code' => 'NG',
            'currency' => 'NGN',
            'flag' => 'https://flagcdn.com/ng.svg',
        ],
        'BJ' => [
            'name' => 'Bénin',
            'code' => 'BJ',
            'currency' => 'XOF',
            'flag' => 'https://flagcdn.com/bj.svg',
        ],
        'TG' => [
            'name' => 'Togo',
            'code' => 'TG',
            'currency' => 'XOF',
            'flag' => 'https://flagcdn.com/tg.svg',
        ],
    ];
    public const OPERATORS_CONFIG = [
        'Sweetch' => [
            'name' => 'Sweetch to Sweetch',
            'image' => 'https://images.seeklogo.com/logo-png/44/1/orange-money-logo-png_seeklogo-440383.png',
            'countries' => ['CI', 'SN', 'ML', 'BF', 'CM'],
            'local_charges' => [
                'percentage' => 0.02,
                'fixed_fee' => 100,
                'withdrawal_fee' => 0.005,
                'currency' => 'XOF'
            ],
            'international_charges' => [
                'percentage' => 0.035,
                'fixed_fee' => 500,
                'withdrawal_fee' => 0.01,
                'currency' => 'XOF'
            ]
        ],
        'Orange Money' => [
            'name' => 'Orange Money',
            'image' => 'https://images.seeklogo.com/logo-png/44/1/orange-money-logo-png_seeklogo-440383.png',
            'countries' => ['CI', 'SN', 'ML', 'BF', 'CM'],
            'local_charges' => [
                'percentage' => 0.02,
                'fixed_fee' => 100,
                'withdrawal_fee' => 0.005,
                'currency' => 'XOF'
            ],
            'international_charges' => [
                'percentage' => 0.035,
                'fixed_fee' => 500,
                'withdrawal_fee' => 0.01,
                'currency' => 'XOF'
            ]
        ],
        'MTN Mobile Money' => [
            'name' => 'MTN Mobile Money',
            'image' => 'https://images.seeklogo.com/logo-png/29/1/mtn-mobile-money-logo-png_seeklogo-297575.png?v=1962448050018930136',
            'countries' => ['CI', 'GH', 'NG', 'CM'],
            'local_charges' => [
                'percentage' => 0.025,
                'fixed_fee' => 100,
                'withdrawal_fee' => 0.007,
                'currency' => 'XOF'
            ],
            'international_charges' => [
                'percentage' => 0.04,
                'fixed_fee' => 500,
                'withdrawal_fee' => 0.012,
                'currency' => 'XOF'
            ]
        ],
        'Moov Money' => [
            'name' => 'Moov Money',
            'image' => 'https://images.seeklogo.com/logo-png/50/2/moov-mauritel-logo-png_seeklogo-509926.png',
            'countries' => ['CI', 'BJ', 'TG'],
            'local_charges' => [
                'percentage' => 0.02,
                'fixed_fee' => 100,
                'withdrawal_fee' => 0.005,
                'currency' => 'XOF'
            ],
            'international_charges' => [
                'percentage' => 0.035,
                'fixed_fee' => 500,
                'withdrawal_fee' => 0.01,
                'currency' => 'XOF'
            ]
        ]
    ];


    public function calculateCharges(CalculateChargesRequest $request)
    {
        $amount = (double)$request->amount;
        $operator = $request->operator;
        $withdrawalfee = $request->withdrawal_fee ?? false;

        // Calculate charges based on operator and amount
        $charges = $this->calculateOperatorCharges($amount, $operator);
        $totalAmount = $amount + $charges+($withdrawalfee ? $this->calculateWithdrawalFee($amount, $operator) : 0);

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $amount,
                'charges' => $charges,
                'total_amount' => $totalAmount,
                'withdrawal_fee' => $withdrawalfee ? $this->calculateWithdrawalFee($amount, $operator) : 0,
                'formatted_total' => number_format($totalAmount, 2) . ' F CFA'
            ]
        ]);
    }
    private function calculateWithdrawalFee($amount, $operator)
    {
        $user = auth()->user();
        $userCountry = $user->country_code ?? 'CM';

        $operatorData = self::OPERATORS_CONFIG[$operator] ?? self::OPERATORS_CONFIG['Orange Money'];
        $isInternational = !in_array($userCountry, $operatorData['countries']);
        $charges = $isInternational ? $operatorData['international_charges'] : $operatorData['local_charges'];

        return $amount * $charges['withdrawal_fee'];
    }
    private function calculateOperatorCharges($amount, $operator)
    {
        // Get user's country code, default to CM if not set
        $user = auth()->user();
        $userCountry = $user->country_code ?? 'CM';

        // Get operator data, fallback to Orange Money if operator not found
        $operatorData = self::OPERATORS_CONFIG[$operator] ?? self::OPERATORS_CONFIG['Orange Money'];

        // Check if transaction is international by verifying if user's country is in operator's countries
        $isInternational = !in_array($userCountry, $operatorData['countries']);

        // Select charges based on whether transaction is international or local
        $charges = $isInternational ? $operatorData['international_charges'] : $operatorData['local_charges'];

        // Calculate total charges (percentage + fixed fee + withdrawal fee)
        // Calculate percentage-based charge
        $percentageCharge = $amount * $charges['percentage'];
        // Calculate withdrawal fee if enabled, otherwise 0

        // Sum up all charges (percentage + fixed fee + withdrawal)
        $totalCharge = $percentageCharge /* + $charges['fixed_fee'] */;

        return $totalCharge;
    }

    public function getPaymentOperators(Request $request)
    {
        $user = auth()->user();
        $userCountry = $user->country_code ?? 'CM';
        $isTransfers = $request->is_transfers ?? false;

        $operators = self::OPERATORS_CONFIG;
        $countries = self::COUNTRIES_CONFIG;  // Use the constant here

        if (!$isTransfers) {
            unset($operators['Sweetch']);
        }
        // Format countries with their available payment methods
        $formattedCountries = [];
        foreach ($countries as $code => $country) {
            $availableOperators = [];
            foreach ($operators as $operator) {
                if (in_array($code, $operator['countries'])) {
                    $availableOperators[] = $operator['name'];
                }
            }

            $formattedCountries[] = [
                'name' => $country['name'],
                'code' => $country['code'],
                'currency' => $country['currency'],
                'flag' => $country['flag'],
                'available_operators' => $availableOperators
            ];
        }

        // Format operators as before
        $formattedOperators = [];
        foreach ($operators as $operator) {
            $isLocal = in_array($userCountry, $operator['countries']);
            $charges = $isLocal ? $operator['local_charges'] : $operator['international_charges'];

            $formattedOperators[] = [
                'name' => $operator['name'],
                'image' => $operator['image'],
                'is_international' => !$isLocal,
                'charges' => $charges,
                'available_countries' => array_map(function ($code) use ($countries) {
                    return $countries[$code];
                }, $operator['countries'])
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_country' => $userCountry,
                'countries' => $formattedCountries,
                'operators' => $formattedOperators
            ]
        ]);
    }

    public function getAnyToAnyOperators(Request $request)
    {


        // Start with empty operators array
        $operators = [];

        // Only include Sweetch if it's a transfer

            $operators['Sweetch'] = self::OPERATORS_CONFIG['Sweetch'];


        // Add new AnyToAny operator
        $operators['AnyToAny'] = [
            'name' => 'AnyToAny Transfer',
            'image' => 'https://example.com/anytoany-logo.png',
            'countries' => array_keys(self::COUNTRIES_CONFIG),
            'local_charges' => [
                'percentage' => 0.03,
                'fixed_fee' => 200,
                'withdrawal_fee' => 0.008,
                'currency' => 'XOF'
            ],
            'international_charges' => [
                'percentage' => 0.045,
                'fixed_fee' => 600,
                'withdrawal_fee' => 0.015,
                'currency' => 'XOF'
            ]
        ];


        return response()->json([
            'success' => true,
            'data' => $operators
        ]);
    }


}
