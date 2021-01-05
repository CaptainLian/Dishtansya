<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Psr\Log\LoggerInterface as Log;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Illuminate\Database\DatabaseManager as DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class UserController extends Controller {
	const COUNT_FAILED_ATTEMPTS_THRESHOLD = 5;
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
		$this->logger->debug('>> UserController.create()');

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
	
	/**
     * Get a JWT via given credentials.
     * TODO: Use the "Authorization Basic" header instead
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
		$this->logger->debug('>> UserController.login()', ['content' => $request->getContent()]);
		$input = $request->json()->all();
		
		//Sanitize inputs
		$inputEmail = $input['email'];
		$inputPassword = $input['password'];
		
		$now = Carbon::now();

		$user = $this->user->where('email', '=', $inputEmail)->first();
		$this->logger->debug('Login attempt', [ 'user' => $user, 'timestamp' => $now, ]);
		if (!$user) {
			$this->logger->debug('Account attempt failed: account doesn\'t exists');
            return response()->json([
				'message' => 'Invalid credentials'
			], 404);
		}
		$isUserLocked = $now->lte($user->locked_until);
		if($isUserLocked) {
			$this->logger->debug('Account attempt failed: account locked');
			// $until = $user->locked_until->diffForHumans();
			return response()->json([
				'message' => "Account locked",
				'until' => $user->locked_until,
			], 409);
		}
		
		$hashedInputPassword = Hash::make($inputPassword);
		$token = Auth::guard('jwt')->attempt([
			'email' => $inputEmail,
			'password' => $inputPassword
		]);
        if (!$token) {
			$this->logger->debug('Account attempt failed: incorrect credentials');
			$userFailedAttempts =($user->count_failed_attempts ? $user->count_failed_attempts : 0) + 1;
			$user->count_failed_attempts = $userFailedAttempts;

			$message = 'Invalid credentials';
			$code = 401;
			if($userFailedAttempts >= self::COUNT_FAILED_ATTEMPTS_THRESHOLD) {
				$timeout = $now->addMinutes(5);
				// $timeout = $now->addSeconds(15);
				$user->locked_until = $timeout;
				$user->count_failed_attempts = 0;
				$this->logger->notice('Locking a user due to threshold reached', [
					'user' => $user,
				]);
				
				$message = 'Invalid credentials. Account locked for 5 minutes';
			}
			$user->save();
			
            return response()->json([
				'message' => $message,
			], $code);
        }

        return response()->json([
			'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('jwt')->factory()->getTTL() * 60
		], 201);
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
}
