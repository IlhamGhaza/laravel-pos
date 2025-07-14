<?php

namespace App\Observers;

use App\Models\User; // Sesuaikan dengan namespace User model Anda
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
// use App\Mail\WelcomeEmail; // Contoh Mail class

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Log::info("User created: {$user->name} (ID: {$user->id}), Email: {$user->email}");

        // Kirim email selamat datang
        // Mail::to($user->email)->send(new WelcomeEmail($user));

        // Jika menggunakan Laravel Shield dan ingin menetapkan peran default jika tidak ada
        // if (!$user->getRoleNames()->count()) {
        //    $user->assignRole('customer'); // Ganti 'customer' dengan nama peran default Anda
        //    Log::info("Assigned default role 'customer' to user: {$user->id}");
        // }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        Log::info("User updated: {$user->name} (ID: {$user->id})");
        if ($user->isDirty('email')) {
            Log::info("User email changed for {$user->name}: Old email {$user->getOriginal('email')}, New email {$user->email}");
            // Tambahkan logika jika email diubah, misal perlu verifikasi ulang
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        Log::info("User deleted: {$user->name} (ID: {$user->id})");
    }
}
