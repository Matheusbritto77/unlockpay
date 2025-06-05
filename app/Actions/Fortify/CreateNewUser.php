<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            'codigo_indicacao' => ['nullable', 'string', 'max:255', 'exists:users,codigo_revendedor'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ])->validate();

        $codigoRevendedor = $this->generateUniqueReferralCode();

        // Calcular início e fim do teste gratuito
        $dataInicio = Carbon::now();
        $dataFim = $dataInicio->copy()->addDays(15);

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'codigo_indicacao' => $input['codigo_indicacao'] ?? null,
            'codigo_revendedor' => $codigoRevendedor,
            'porcent_resseller' => 15.00,
            'whatsapp' => $input['whatsapp'] ?? null,
            'plan_id' => 2, // plano de teste
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ]);
    }

    protected function generateUniqueReferralCode()
    {
        do {
            $codigo = 'REF' . Str::random(8);
        } while (User::where('codigo_revendedor', $codigo)->exists());

        return $codigo;
    }
}