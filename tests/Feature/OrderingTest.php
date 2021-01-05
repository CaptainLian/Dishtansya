<?php
namespace Tests\Feature;

use Test\Feature\RegistrationTest;

use Psr\Log\LoggerInterface as Log;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;

use ProductSeeder;

class OrderingTest extends TestCase {
    use RefreshDatabase;

    const API_REGISTER = '/api/register';
    const API_ORDER = '/api/order';
    
    const TEST_EMAIL = 'backend@multisyscorp.com';
    const TEST_PASSWORD = 'test123';

    private Log $logger;

    private $userModel;
    private $productModel;
    private $orderModel;

    private User $user;
    
    protected function setUp(): void {
        parent::setUp();

        $userModel = $this->app->make(User::class);
        $this->userModel = $userModel;
        $this->productModel = $this->app->make(Product::class);
        $this->orderModel = $this->app->make(Order::class);
        $this->logger = $this->app->make(Log::class);
        
        //Register the user
        $response = $this->postJson(self::API_REGISTER, [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);
        $response->assertStatus(201)
            ->assertJson([  
                'message' => 'User successfully registered',
            ]);

        $this->user = User::firstWhere('email', '=', self::TEST_EMAIL);
        $this->seed(ProductSeeder::class);
    }

    /**
     * @test
     */
    public function testOrdering() : void {
        //Testing product is not enough
        $response = $this->actingAs($this->user, 'jwt')
            ->postJson(self::API_ORDER, [
                'product_id' => 1,
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'You have successfully ordered this product',
            ]);
        
        //Test Availability Checking
        //TODO: On its own function
        $response = $this->actingAs($this->user, 'jwt')
            ->postJson(self::API_ORDER, [
                'product_id' => 1,
                'quantity' => 1000000000,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Failed to order this product due to unavailability of the stock',
            ]);
    }
}
