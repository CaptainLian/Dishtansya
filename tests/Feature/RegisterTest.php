<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
class RegisterTest extends TestCase {   
    use RefreshDatabase;

    const TEST_EMAIL = 'backend@multisyscorp.com';
    const TEST_PASSWORD = 'test123';

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
    public function testRegisterUser() : void {
        $response = $this->postJson('/api/register', [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User successfully registered',
            ]);
    }

    
    /**
     * Test if the registration works and the API is able to handle case
     * @test
     * @depends testRegisterUser
     */
    public function testAlreadyRegistered() : void {
        $response = $this->postJson('/api/register', [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'User successfully registered',
            ]);
    }
    
    
    // /**
    //  * Test if the registration works and the API is able to handle case
    //  * @test
    //  * @depends testRegisterUser
    //  */
    // public function testRegisteredUserExistsInDb() {
    //     $user = $this->user->where('email', '=', self::TEST_EMAIL)->first();
    //     $this->assertNotNull($user);
    // }
}
