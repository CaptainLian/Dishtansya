<?php
namespace Tests\Feature;

use Psr\Log\LoggerInterface as Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Product;
use App\Http\Controllers\UserController;
class UserTest extends TestCase
{
    use RefreshDatabase;

    const API_ORDER = '/api/order';
    const API_REGISTER = '/api/register';
    const API_LOGIN = '/api/login';
    
    const TEST_EMAIL = 'backend@multisyscorp.com';
    const TEST_PASSWORD = 'test123';
    const TEST_PASSWORD_INCORRECT = '1234567891011';

    const COUNT_FAILED_ATTEMPTS_THRESHOLD = UserController::COUNT_FAILED_ATTEMPTS_THRESHOLD;

    private $userModel;

    protected function setUp(): void {
        parent::setUp();
        //Load models
        $this->productModel = $this->app->make(Product::class);
        $this->userModel = $this->app->make(User::class);
        //Load other dependencies through injection from app container
        $this->logger = $this->app->make(Log::class);
    }

    /**
     * Tests the registration of a user
     * @test
     */
    public function testRegistration() : User {
        //Register the user
        $response = $this->postJson(self::API_REGISTER, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);
        $response->assertStatus(201)
            ->assertJson([  
                'message' => 'User successfully registered',
            ]);
        
        //Check if the user exists in the database
        $user = $this->userModel->where('email', '=', self::TEST_EMAIL)->first();
        $this->assertNotNull($user);

        //Login using correct credentials
        $response = $this->postJson(self::API_LOGIN, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);
        $response->assertStatus(201);

        //Login using incorrect credentials
        $response = $this->postJson(self::API_LOGIN, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD_INCORRECT,
        ]);
        $response->assertStatus(401);

        return $user;
    }

    /**
     * Test to see if the API can handle duplicates
     * @depends testRegistration
     * @test
     */
    public function testRegistrationDuplicate(User $user) : void {
        //Register the user
        $response = $this->postJson(self::API_REGISTER, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User successfully registered',
            ]);
        
        //API should return user is registed
        $response = $this->postJson(self::API_REGISTER, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);
        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Email already taken',
            ]);
    }

    /**
     * @depends testRegistration
     * @test
     */
    public function testLoginLocking(User $user) : void {
        //Register the user
        $response = $this->postJson(self::API_REGISTER, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User successfully registered',
            ]);
        
            
        for($count = 1; $count < self::COUNT_FAILED_ATTEMPTS_THRESHOLD; ++$count) {
            //Login using incorrect credentials
            $response = $this->postJson(self::API_LOGIN, [
                'email' => self::TEST_EMAIL,
                'password' => self::TEST_PASSWORD_INCORRECT,
            ]);
            $response->assertStatus(401);
        }

        $response = $this->postJson(self::API_LOGIN, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD_INCORRECT,
        ]);
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials. Account locked for 5 minutes',
            ]);

        $response = $this->postJson(self::API_LOGIN, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD_INCORRECT,
        ]);
        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Account locked',
            ]);
    }
}