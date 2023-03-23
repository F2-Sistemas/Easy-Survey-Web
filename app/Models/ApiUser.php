<?php

namespace App\Models;

use App\Traits\ModelKeys;
// use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Http;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Session;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\ApiUser
 *
 * @property-read string $id
 * @property-read string $name
 * @property-read string $email
 * @property-read string $token
 * @property-read string $password
 * @property-read Collection $otherData
 * …
 */

#[\AllowDynamicProperties]
class ApiUser implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    // use HasApiTokens;
    // use Notifiable;
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use ModelKeys;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributeRules.
     *
     * @var array<string, string>
     */
    protected static array $attributeRules = [
        'id' => 'required|uuid',
        'name' => 'required|string|min:2',
        'email' => 'required|email',
        'token' => 'required|string|min:20',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected array $hidden = [
        'hidden',
        'password',
        'remember_token',
        'rememberTokenName',
    ];

    public string $id;
    public string $name;
    public string $email;
    public string $token;
    public string $password = 'no-pass';
    public Collection $otherData;
    protected static bool $allowDynamicProperties = false;

    public function __construct(
        Collection|array $attributes
    ) {
        static::validate($attributes);
        static::$allowDynamicProperties = true;

        $this->otherData = \collect();

        \collect($attributes)->each(fn ($item, $key) => $this->set($key, $item));

        static::$allowDynamicProperties = false;
    }

    /**
     * function validate
     *
     * @param array $attributes
     * @param ?array $attributeRules
     *
     * @return void
     */
    public static function validate(array $attributes, ?array $attributeRules = null): void
    {
        Validator::make(
            $attributes,
            $attributeRules ?? static::$attributeRules
        )->validate();
    }

    /**
     * function toArray
     *
     * @param ?array $only
     * @return array
     */
    public function toArray(?array $only = \null): array
    {
        $items = \collect(
            \array_filter(
                \get_object_vars($this),
                fn ($attribute) => !\in_array(
                    $attribute,
                    $this->hidden,
                    \true
                ),
                \ARRAY_FILTER_USE_KEY
            )
        );

        if ($only) {
            $items = $items->filter(fn ($attribute) => in_array($attribute, $only, \true));
        }

        return $items->all();
    }

    public function set(string $name, mixed $value): void
    {
        if (!$name || !\is_string($name) || !\trim($name)) {
            return;
        }

        if (!\in_array($name, \array_keys(static::$attributeRules), true)) {
            $this->otherData->put($name, $value);

            return;
        }

        $this->{$name} = $value;
    }

    public function __set(string $name, mixed $value): void
    {
        if (!static::$allowDynamicProperties) {
            return;
        }

        $this->set($name, $value);
    }

    /**
     * function login
     *
     * @param array $loginData
     * @return ApiUser|MessageBag
     */
    public static function login(array $loginData): ApiUser|MessageBag
    {
        $loginData = Validator::make($loginData, [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ])->validate();

        $response = static::httpClient()->post('__/auth/login', $loginData);
        $responseData = (array) $response->json();

        $message = Arr::get(
            $responseData,
            'message'
        );

        if ($response->status() != 200) {
            if ($message) {
                Session::flash('success', $message);
            }

            return new MessageBag([
                'message' => Arr::get($responseData, 'message'),
                'errors' => (array) Arr::get($responseData, 'errors'),
                'status' => $response->status(),
            ]);
        }

        if ($message) {
            Session::flash('success', $message);
        }

        return new static([
            'id' => Arr::get($responseData, 'user.id'),
            'name' => Arr::get($responseData, 'user.name'),
            'email' => Arr::get($responseData, 'user.email'),
            'token' => Arr::get($responseData, 'token'),
        ]);
    }

    /**
     * function register
     *
     * @param array $registerData
     * @return ApiUser|MessageBag
     */
    public static function register(array $registerData): ApiUser|MessageBag
    {
        $registerData['password_confirmation'] = $registerData['password'] ?? \null;

        $registerData = Validator::make($registerData, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ])->validate();

        $registerData['password_confirmation'] = $registerData['password'] ?? \null;

        $response = static::httpClient()->post('__/auth/register', $registerData);
        $responseData = (array) $response->json();

        $message = Arr::get(
            $responseData,
            'message'
        );

        $status = $response->status();

        try {
            if ($status != 201) {
                if ($message) {
                    Session::flash('error', $message);
                }

                return new MessageBag([
                    'message' => $message,
                    'errors' => (array) Arr::get($responseData, 'errors'),
                    'status' => $status,
                ]);
            }
        } catch (\Throwable $th) {
            \Log::error($th);

            return new MessageBag([
                'message' => app()->environment(['production', 'beta']) ? $message : $th->getMessage(),
                'errors' => (array) Arr::get($responseData, 'errors'),
                'status' => 500,
            ]);
        }

        if ($message) {
            Session::flash('success', $message);
        }

        return new static([
            'id' => Arr::get($responseData, 'user.id'),
            'name' => Arr::get($responseData, 'user.name'),
            'email' => Arr::get($responseData, 'user.email'),
            'token' => Arr::get($responseData, 'token'),
        ]);
    }

    /**
     * function httpClient
     *
     * @param array $headers
     *
     * @return PendingRequest
     */
    public static function httpClient(array $headers = []): PendingRequest
    {
        return Http::apiServer($headers);
    }
}
