<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use Psr\Log\LoggerInterface as Log;
use Illuminate\Database\DatabaseManager as DB;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    private Log $logger;
    private DB $db;
    private User $user;
    private Product $product;
    private Order $order;

    public function __construct(
        Log $logger,
        DB $db,
        User $user,
        Product $product,
        Order $order
    ) {
        $this->logger = $logger;
		$this->db = $db;
        $this->user = $user;
        $this->product = $product;
        $this->order = $order;
        $this->middleware('auth:jwt');
    }

    public function order(Request $request) {
        $this->logger->debug('>> OrderController.order()', ['content' => $request->getContent()]);
        $user = auth()->user();

        $input = $request->json()->all();
        $inputProductId = $input['product_id'];
        $inputQuantity = $input['quantity'];

        $product = $this->product->find($inputProductId);
        if(!$product) {
            $this->logger->debug('Product doesn\'t exsits', ['product_id' => $inputProductId]);
            return response()->json([
                'message' => 'Product does not exists'
            ], 404);
        }
        $this->logger->info('Order attempt for product', [  
            'triggeringUser' => $user,  
            'product' => $product,
            'orderQuantity' => $inputQuantity,
        ]);
        
        $notEnoughStock = $product->availableStock < $inputQuantity;
        if($notEnoughStock) {
            return response()->json([
                'message' => 'Failed to order this product due to unavailability of the stock',
            ], 400);
        }
        
        try {
            $this->db->beginTransaction();
            
            $order = $this->order->create([
                'user' => $user->getKey(),
                'product' => $product->getKey(),
                'amount' => $inputQuantity,
            ]);
            $product->availableStock -= $inputQuantity;
            
            $order->save();
            $product->save();

            $this->db->commit();
            $this->logger->info('Order placed', ['order' => $order]);
            return response()->json([
                'message' => 'You have successfully ordered this product',
            ], 201);
        } catch (\Throwable $th) {
            $this->logger->error('Failed to place order for product', [ 
                'triggeringUser' => $user, 
                'product' => $product, 
                'orderQuantity' => $inputQuantity, 
                'message' => $th->getMessage(),
                'stackTrace' => $th->getTraceAsString(), 
            ]);

            $this->db->rollback();
            throw $th;
            return response()->json([
                'message' => 'Failed to save order'
            ], 500);
        }//end try-catch
        //dead code
    }//end function order
}
