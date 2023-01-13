<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'mail:test')]
class SendTestMailCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : The email address to send the test mail to}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'mail:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the mail configuration by sending a test email to the given address';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $email = $this->argument('email');

        try {
        Mail::raw('This is a test email.', function ($message) use ($email) {
            $message->to($email);
        });

        $this->components->info('Test email sent successfully! Check your inbox.');
        } catch(\Exception $e) {
            $this->components->error('Test email sending failed!');
            $this->error('Unable to send test email: '.$e->getMessage());
        }
    }
}
