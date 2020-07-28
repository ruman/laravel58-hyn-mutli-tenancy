<?php
namespace App\Console\Commands;

// use App\Mail\WebsiteCreated;
use App\User;
use App\WebsiteHasEmail;
use Hyn\Tenancy\Contracts\Repositories\CustomerRepository;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Customer;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CreateTenant extends Command
{
	protected $signature = 'tenant:create {hostname} {first_name} {last_name} {email} {--password=} {--demo_data=}';
	protected $description = 'Creates a tenant with the provided name and email address e.g. php artisan tenant:create foohost foo bar foo@bar.com --password=yourpassword';
	public function handle()
	{
		$hostname = $this->argument('hostname');
		$first_name = $this->argument('first_name');
		$last_name = $this->argument('last_name');
		$email = $this->argument('email');
		$password = $this->option('password');
		$demo_data = $this->option('demo_data');

		//check if name exist in tenant
		if ($this->tenantExists($hostname)) {
			$this->error("A tenant with name '{$hostname}' already exists.");
			return;
		}


		//create tenant website
		$website = $this->createTenant( $hostname );
		//Map email with website
		WebsiteHasEmail::create([
			'website_id' => $website->id,
			'email' => $email
		]);
		//set tenant website in environment
		app( Environment::class )->tenant( $website );

		Artisan::call( 'migrate' );
		Artisan::call( 'passport:install' );

		//Seed the roles into the tenant database
		Artisan::call( 'db:seed --class=RolePermissionSeeder' );


		//run db migration to setup tenant database
		//Artisan::call("tenancy:migrate --website_id={$website->id} --seed");

		//generate random password
		if(!$password){
			$password = str_random();
		}
		//create tenant user and automatically assign as role Admin
		$this->addAdmin( $first_name, $last_name, $email, $password );
		if($demo_data){
			$this->seedDemoData();
		}

		//send the email
		/*$mail = Mail::to($email);
		$mail->send(new WebsiteCreated($email, $password, $website));*/

		//add tenant
		$this->info("Tenant '{$hostname}' is created and is now accessible at {$website->hostnames[0]->fqdn}");
		$this->info("Admin {$email} can log in using password {$password}");
	}

	private function tenantExists($name)
	{
		return Hostname::where('fqdn', $name.'.'.Config::get('tenancy.hostname.default'))->exists();
	}

	private function createTenant($name)
	{

		$website = new Website();

		app(WebsiteRepository::class)->create($website);


		$hostname = new Hostname;
		$baseUrl = Config::get('tenancy.hostname.default');
		$hostname->fqdn = "{$name}.{$baseUrl}";
		$hostname = app(HostnameRepository::class)->create($hostname);
		app(HostnameRepository::class)->attach($hostname, $website);

		return $website;
	}
	private function addAdmin($first_name, $last_name, $email, $password)
	{
		$user = User::create([
			'first_name' => $first_name,
			'last_name' => $last_name,
			'email' => $email,
			'password' => Hash::make($password),
			'timezone' => 'UTC'
		]);
		$user->assignRole('Admin');
		return $user;
	}

	private function seedDemoData()
	{
		$demo_data_sql = Storage::disk('s3')->get('demo_data.sql');
		DB::unprepared($demo_data_sql);
	}
}
