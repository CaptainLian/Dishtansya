<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

use Psr\Log\LoggerInterface as Log;
use Illuminate\Database\DatabaseManager as DB;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class UserController extends Controller {
	private Log $logger;
	private DB $db;
	private User $user;

	public function __construct(
		Log $logger,
		DB $db,
		User $user
	) { 
		$this->logger = $logger;
		$this->db = $db;
		$this->user = $user;

		//$this->middleware('auth:jwt')->except(['create', 'getAll', 'getByEmail', 'login']);
	}

	/**
	 * Register a user
	 */
	public function create(Request $request) {
		$this->logger->debug('>> UserController.create()', ['content' => $request->getContent()]);

		try {
			$input = $request->json()->all();
			
			//Sanitize inputs
			$inputEmail = $input['email'];
			$inputPassword = $input['password'];
			
			//Check if user exists.
			$userExists = $this->user->where('email', '=', $inputEmail)->first();
			$this->logger->debug('user exists?', ['user' => $userExists]);
			if($userExists) {
				return response()->json([
					'message' => 'Email already taken'
				], 400);
			}
			
			$hashedPassword = Hash::make($inputPassword);
			//Create user if not exists.
			$user = $this->user->create([
				'email' => $inputEmail,
				'password' => $hashedPassword,
			]);
			$this->logger->info('Created user', ['user' => $user]);
			
			//Respond
			return response()->json([
				'message' => 'User successfully registered',
			], 201);
		} catch (Throwable $th) {
			$this->logger->error('Error encountered in UserController.create()', ['stackTrace' => $th->getTraceAsString(), 'message' => $th->getMessage()]);

			throw $th; //Rethrow
		}
	}

	public function getAll(Request $request) { 
		$this->logger->debug('>> UserController.getAll()', ['content' => $request->getContent()]);
		try {
			$users = $this->user->all();
			return $users->toJson();
		} catch (\Throwable $th) {
			$this->logger->error($th->getTraceAsString());
			throw $th;
		}
	}

	public function getByEmail(Request $request) {
		$this->logger->debug('>> UserController.getByEmail()', ['content' => $request->getContent()]);
	}

	/**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
		$this->logger->debug('>> UserController.login()', ['content' => $request->getContent()]);
		$input = $request->json()->all();
		
		//Sanitize inputs
		$inputEmail = $input['email'];
		$inputPassword = $input['password'];
		$hashedInputPassword = Hash::make($inputPassword);
		$token = Auth::guard('jwt')->attempt([
			'email' => $inputEmail,
			'password' => $inputPassword
		]);
        if (!$token) {
            return response()->json([
				'message' => 'Invalid credentials'
			], 401);
        }

        return response()->json([
			'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('jwt')->factory()->getTTL() * 60
		], 201);
	}
}
