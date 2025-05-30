<?php

namespace App\Http\Controllers\Api\Utils;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;

class UtilsController extends Controller
{
    public function getCountriesAndOperators(Request $request)
    {
        $user = auth()->user();
        $userCountry = $user->country_code ?? 'CM';

        // Get constants from TransactionController
        $countriesConfig = TransactionController::COUNTRIES_CONFIG;
        $operatorsConfig = TransactionController::OPERATORS_CONFIG;

        // Remove Sweetch operator
        unset($operatorsConfig['Sweetch']);

        // Format countries with their available payment methods including full operator details
        $formattedCountries = [];
        foreach ($countriesConfig as $code => $country) {
            $countryOperators = [];
            foreach ($operatorsConfig as $operator) {
                if (in_array($code, $operator['countries'])) {
                    $isLocal = in_array($userCountry, $operator['countries']);
                    $charges = $isLocal ? $operator['local_charges'] : $operator['international_charges'];

                    $countryOperators[] = [
                        'name' => $operator['name'],
                        'image' => $operator['image'],
                        'is_international' => !$isLocal,
                        'charges' => $charges
                    ];
                }
            }

            $formattedCountries[] = [
                'name' => $country['name'],
                'code' => $country['code'],
                'currency' => $country['currency'],
                'flag' => $country['flag'],
                'operators' => $countryOperators
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user_country' => $userCountry,
                'countries' => $formattedCountries
            ]
        ]);
    }
}
