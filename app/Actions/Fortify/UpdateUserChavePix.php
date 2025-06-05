<?php


namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserChavePix implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  mixed  $user
     * @param  array  $input
     * @return void
     */
    public function update($user, array $input)
    {
        Validator::make($input, [
            'chave_pix' => ['nullable', 'string', 'max:255'],
        ])->validateWithBag('updateChavePix');

        $user->forceFill([
            'chave_pix' => $input['chave_pix'],
        ])->save();
    }
}