<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\User;
use App\Email\UserRegistered;

use Illuminate\Support\Facades\Mail;

class RegistrationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private UserRegistered $mail;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(UserRegistered $mail)
    {
        $this->mail = $mail;
    }

    public function getMail() 
    {
        return $this->mail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
