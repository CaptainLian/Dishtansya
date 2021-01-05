<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\WithFaker; //LOOK AT THE MOVES, LOOK AT THE CLENSE, FAKER WHAT WAS THAT?!
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\User;
class RegisterTest extends TestCase {   
    use RefreshDatabase;

    const API_REGISTER = '/api/register';
    const API_LOGIN = '/api/login';
    const TEST_EMAIL = 'backend@multisyscorp.com';
    const TEST_PASSWORD = 'test123';
    const TEST_PASSWORD_INCORRECT = '1234567891011';

    private User $user;

    /**
     * Undocumented function
     * 
     */
    public function setUp() : void{
        parent::setUp();

        $this->user = $this->app->make(User::class);
    }

    /**
     * Tests the registration of a user
     * @test
     */
    public function testRegistration() : void {
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
            
        //Check if the user exists in the database
        $user = $this->user->where('email', '=', self::TEST_EMAIL)->first();
        $this->assertNotNull($user);
    }
    
    /**
     * @test
     * @depends testRegistration
     * @return void
     */
    public function testLogin() : void {
        //Register the user
        $response = $this->postJson(self::API_REGISTER, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User successfully registered',
            ]);
        
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
    }
}
