<?php

namespace Kemboielvis\MpesaSdkPhp;

use Kemboielvis\MpesaSdkPhp\Helpers\AccountBalance;
use Kemboielvis\MpesaSdkPhp\Helpers\BusinessToCustomer;
use Kemboielvis\MpesaSdkPhp\Helpers\CustomerToBusiness;
use Kemboielvis\MpesaSdkPhp\Helpers\Reversal;
use Kemboielvis\MpesaSdkPhp\Helpers\Stk;
use Kemboielvis\MpesaSdkPhp\Helpers\TransactionStatus;
use Kemboielvis\MpesaSdkPhp\Http\ApiClient;

class Mpesa
{
    protected string $consumer_key = '';
    protected string $consumer_secret = '';
    protected string $business_code = '';
    protected string $pass_key = '';
    protected string $transaction_type = '';
    private string $token_url = '/oauth/v1/generate?grant_type=client_credentials';
    protected string $phone_number = '';

    protected string $amount = '';
    protected string $call_back_url = '';

    protected string $security_credential = '';

    protected object $response;
    protected string $queue_timeout_url = '';
    protected string $result_url = '';

    private string $baseUrl = '';

    private string $env = '';
    private ApiClient $apiClient;

    /**
     * Mpesa constructor.
     *
     * @param string|null $key consumer key
     * @param string|null $secret consumer secret
     * @param string|null $env environment. either "live" or "sandbox"
     */
    public function __construct(?string $key = null, ?string $secret = null, ?string $env = null)
    {
        $this->setCredentials($key, $secret, $env);
        $this->apiClient = new ApiClient($this->baseUrl, $this->token_url, $key, $secret);
    }

    /**
     * Sets the credentials for the Mpesa API.
     *
     * This method initializes the consumer key, consumer secret, and environment
     * for the Mpesa API. It updates the base URL based on the environment and
     * configures the ApiClient instance with the provided credentials.
     *
     * @param string|null $consumer_key The consumer key for the M-Pesa API.
     * @param string|null $consumer_secret The consumer secret for the M-Pesa API.
     * @param string|null $env The environment for the M-Pesa API. Either "live" or "sandbox".
     *
     * @return Mpesa Returns the current instance of the Mpesa class.
     */
    public function setCredentials(?string $consumer_key = null, ?string $consumer_secret = null, ?string $env = null): Mpesa
    {
        if (null != $consumer_key) {
            $this->consumerKey($consumer_key);
        }
        if (null != $consumer_secret) {
            $this->consumerSecret($consumer_secret);
        }
        if (null != $env) {
            $this->env($env);
        }
        $this->baseUrl = ('live' == strtolower($this->env)) ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

        $this->apiClient = new ApiClient($this->baseUrl, $this->token_url, $this->consumer_key, $this->consumer_secret);

        return $this;
    }

    /**
     * Gets the current timestamp.
     *
     * This method returns the current timestamp in the 'YmdHis' format, which is
     * the format required by the M-Pesa API for certain operations.
     *
     * @return string The current timestamp in the 'YmdHis' format.
     */
    public function timestamp(): string
    {
        return date('YmdHis');
    }

    /**
     * Generates the password for the M-Pesa API.
     *
     * This method returns a base64 encoded string consisting of the business
     * code, pass key, and the current timestamp.
     *
     * @return string The base64 encoded string.
     */
    public function password(): string
    {
        return base64_encode($this->business_code . $this->pass_key . $this->timestamp());
    }

    /**
     * Sets the business code for the Mpesa transaction.
     *
     * This method assigns the provided business code to the instance and
     * returns the current instance for method chaining.
     *
     * @param string $business_code The business code used for the transaction.
     *
     * @return static Returns the current instance of the Mpesa class.
     */
    public function businessCode(string $business_code): static
    {
        $this->business_code = $business_code;

        return $this;
    }

    /**
     * Sets the environment for the M-Pesa API.
     *
     * This method sets the environment to either "live" or "sandbox" and
     * returns the current instance for method chaining.
     *
     * @param string $env The environment for the M-Pesa API. Either "live" or "sandbox".
     *
     * @return Mpesa Returns the current instance of the Mpesa class.
     */
    public function env(string $env): Mpesa
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Sets the amount for the Mpesa transaction.
     *
     * This method assigns the provided amount to the instance and
     * returns the current instance for method chaining.
     *
     * @param int $amount The amount to be transacted.
     *
     * @return static Returns the current instance of the Mpesa class.
     */
    public function amount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Sets the phone number for the Mpesa transaction.
     *
     * This method assigns the provided phone number to the instance and
     * returns the current instance for method chaining.
     *
     * @param string $phone The phone number to be used for the transaction.
     *
     * @return static Returns the current instance of the Mpesa class.
     */
    public function phoneNumber(string $phone): static
    {
        $this->phone_number = $phone;

        return $this;
    }

    /**
     * Sets the consumer key for the Mpesa API.
     *
     * This method assigns the provided consumer key to the instance and
     * returns the current instance for method chaining.
     *
     * @param string $consumer_key The consumer key for the Mpesa API.
     *
     * @return static Returns the current instance of the Mpesa class.
     */
    public function consumerKey(string $consumer_key): static
    {
        $this->consumer_key = $consumer_key;

        return $this;
    }

    /**
     * Sets the consumer secret for the Mpesa API.
     *
     * This method assigns the provided consumer secret to the instance and
     * returns the current instance for method chaining.
     *
     * @param string $consumer_secret The consumer secret for the Mpesa API.
     *
     * @return static Returns the current instance of the Mpesa class.
     */
    public function consumerSecret(string $consumer_secret): static
    {
        $this->consumer_secret = $consumer_secret;

        return $this;
    }

    /**
     * Sets the pass key for the Mpesa API.
     *
     * This method assigns the provided pass key to the instance and
     * returns the current instance for method chaining.
     *
     * @param string $pass_key The pass key for the Mpesa API.
     *
     * @return static Returns the current instance of the Mpesa class.
     */
    public function passKey(string $pass_key): static
    {
        $this->pass_key = $pass_key;

        return $this;
    }

    /**
     * Retrieves the response from the Mpesa API.
     *
     * @return object The response from the Mpesa API.
     */
    public function response(): object
    {
        return $this->response;
    }

    /**
     * Sets the security credential for the M-Pesa transaction.
     *
     * This method encrypts the initiator password using the AES-256-CBC
     * encryption method with a random initialization vector (IV) and a
     * predefined password. The encrypted data is then base64 encoded and
     * assigned to the instance's security credential.
     *
     * @param string $initiator_password The initiator password to encrypt.
     *
     * @return Mpesa Returns the current instance of the Mpesa class.
     */
    public function securityCredential(string $initiator_password): Mpesa
    {
        $method = 'aes-256-cbc';
        $password = 'mypassword';
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $this->security_credential = base64_encode($iv . openssl_encrypt("{$initiator_password} + Certificate", $method, $password, 0, $iv));

        return $this;
    }

    /**
     * Sets the result URL to receive the response from the Mpesa API.
     *
     * @param string $result_url The result URL to receive the response from the Mpesa API.
     *
     * @return Mpesa Returns the current instance of the Mpesa class.
     */
    public function resultUrl(string $result_url): Mpesa
    {
        $this->result_url = $result_url;

        return $this;
    }

    /**
     * Sets the queue timeout URL to receive timeout notifications from the Mpesa API.
     *
     * This method assigns the provided queue timeout URL to the instance and
     * returns the current instance for method chaining.
     *
     * @param string $timeout_url The queue timeout URL to receive timeout notifications from the Mpesa API.
     *
     * @return Mpesa Returns the current instance of the Mpesa class.
     */
    public function queueTimeoutUrl(string $timeout_url): Mpesa
    {
        $this->queue_timeout_url = $timeout_url;

        return $this;
    }


    /**
     * Executes a cURL request to the specified URL with the provided data using the ApiClient.
     *
     * This method delegates the cURL request to the ApiClient's `curls` method,
     * sending the given data to the specified endpoint URL. The response from
     * the ApiClient's `curls` method is returned.
     *
     * @param array<int,mixed> $data The data to be sent in the POST request.
     * @param string $url The endpoint URL for the cURL request.
     * @return mixed The response from the ApiClient's `curls` method.
     */
    public function curls(array $data, string $url)
    {
        return $this->apiClient->curls($data, $url);
    }

    /**
     * Initializes a new instance of the Stk class with the current
     * configuration.
     *
     * This method creates a new instance of the Stk class with the current
     * configuration and returns it.
     *
     * @return Stk Returns an instance of the Stk class.
     */
    public function stk(): Stk
    {
        return new Stk([
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
            'base_url' => $this->baseUrl,
            'business_code' => $this->business_code,
            'transaction_type' => $this->transaction_type,
            'amount' => $this->amount,
            'phone_number' => $this->phone_number,
            'call_back_url' => $this->call_back_url,
        ]);
    }

    /**
     * Initializes a new instance of the CustomerToBusiness class with the current configuration.
     *
     * This method creates a new instance of the CustomerToBusiness class using
     * the stored consumer key, consumer secret, and base URL. It returns the
     * created instance, allowing for method chaining and further configuration.
     *
     * @return CustomerToBusiness Returns an instance of the CustomerToBusiness class.
     */
    public function customerToBusiness(): CustomerToBusiness
    {
        return new CustomerToBusiness($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    /**
     * Initializes a new instance of the BusinessToCustomer class with the current configuration.
     *
     * This method creates a new instance of the BusinessToCustomer class using
     * the stored consumer key, consumer secret, and base URL. It returns the
     * created instance, allowing for method chaining and further configuration.
     *
     * @return BusinessToCustomer Returns an instance of the BusinessToCustomer class.
     */
    public function businessToCustomer(): BusinessToCustomer
    {
        return new BusinessToCustomer($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    /**
     * Initializes a new instance of the AccountBalance class with the current configuration.
     *
     * This method creates a new instance of the AccountBalance class using
     * the stored consumer key, consumer secret, and base URL. It returns the
     * created instance, allowing for method chaining and further configuration.
     *
     * @return AccountBalance Returns an instance of the AccountBalance class.
     */
    public function checkBalance(): AccountBalance
    {
        return new AccountBalance($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    /**
     * Initializes a new instance of the TransactionStatus class with the current configuration.
     *
     * This method creates a new instance of the TransactionStatus class using
     * the stored consumer key, consumer secret, and base URL. It returns the
     * created instance, allowing for method chaining and further configuration.
     *
     * @return TransactionStatus Returns an instance of the TransactionStatus class.
     */
    public function transactionStatus(): TransactionStatus
    {
        return new TransactionStatus($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    /**
     * Initializes a new instance of the Reversal class with the current configuration.
     *
     * This method creates a new instance of the Reversal class using
     * the stored consumer key, consumer secret, and base URL. It returns the
     * created instance, allowing for method chaining and further configuration.
     *
     * @return Reversal Returns an instance of the Reversal class.
     */
    public function reversal(): Reversal
    {
        return new Reversal($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }
}
