<?php

namespace Kemboielvis\MpesaSdkPhp;

use Kemboielvis\MpesaSdkPhp\Abstracts\ApiClient;
use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaConfig;
use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaInterface;
use Kemboielvis\MpesaSdkPhp\Services\AccountBalanceService;
use Kemboielvis\MpesaSdkPhp\Services\AgeOnNetworkService;
use Kemboielvis\MpesaSdkPhp\Services\B2BExpressCheckoutService;
use Kemboielvis\MpesaSdkPhp\Services\B2CAccountTopUpService;
use Kemboielvis\MpesaSdkPhp\Services\B2CHakikishaService;
use Kemboielvis\MpesaSdkPhp\Services\BusinessBuyGoodsService;
use Kemboielvis\MpesaSdkPhp\Services\BusinessPayBillService;
use Kemboielvis\MpesaSdkPhp\Services\BusinessToCustomerService;
use Kemboielvis\MpesaSdkPhp\Services\BusinessToPochiService;
use Kemboielvis\MpesaSdkPhp\Services\CustomerToBusinessService;
use Kemboielvis\MpesaSdkPhp\Services\DynamicQrService;
use Kemboielvis\MpesaSdkPhp\Services\IotSimService;
use Kemboielvis\MpesaSdkPhp\Services\LipaNaBongaService;
use Kemboielvis\MpesaSdkPhp\Services\MobileDataBundlesService;
use Kemboielvis\MpesaSdkPhp\Services\MobileNumberValidationService;
use Kemboielvis\MpesaSdkPhp\Services\PullTransactionsService;
use Kemboielvis\MpesaSdkPhp\Services\ReversalService;
use Kemboielvis\MpesaSdkPhp\Services\StkService;
use Kemboielvis\MpesaSdkPhp\Services\TaxRemittanceService;
use Kemboielvis\MpesaSdkPhp\Services\TransactionStatusService;
use Kemboielvis\MpesaSdkPhp\Abstracts\TokenManager;

/**
 * Main M-Pesa SDK class.
 */
class Mpesa
{
    private MpesaConfig $config;

    private MpesaInterface $client;

    /**
     * Create a new Mpesa instance.
     *
     * @param string|null $consumerKey    The consumer key
     * @param string|null $consumerSecret The consumer secret
     * @param string      $environment    The environment (live or sandbox)
     */
    public function __construct(
        string $consumerKey = null,
        string $consumerSecret = null,
        string $environment = 'sandbox'
    ) {
        $this->config = new MpesaConfig($consumerKey, $consumerSecret, $environment);
        $this->client = new ApiClient($this->config);
    }

    /**
     * Set the M-Pesa configuration.
     *
     * @param MpesaConfig $config The M-Pesa configuration.
     *
     * @return self
     */
    public function setConfig(MpesaConfig $config)
    {
        $this->config = $config;
        $this->client = new ApiClient($this->config);
        return $this;
    }

    /**
     * Get the M-Pesa configuration.
     *
     * @return MpesaConfig The M-Pesa configuration.
     */
    public function getConfig(): MpesaConfig
    {
        return $this->config;
    }

    /**
     * Set the credentials for the M-Pesa API.
     *
     * @param string      $consumerKey    The consumer key
     * @param string      $consumerSecret The consumer secret
     * @param string      $environment    The environment (live or sandbox)
     * @param string|null $storeFile      Optional: token store file path; if null, keep current
     *
     * @return self
     */
    public function setCredentials(string $consumerKey, string $consumerSecret, string $environment = 'sandbox', ?string $storeFile = null): self
    {
        $effectiveStoreFile = $storeFile ?? $this->config->getStoreFile();
        $this->config = new MpesaConfig($consumerKey, $consumerSecret, $environment, null, null, null, null, null, $effectiveStoreFile);
        $this->client = new ApiClient($this->config);

        return $this;
    }

    /**
     * Set the file to store the token and refresh the client so it takes effect immediately.
     *
     * @param string $storeFile The file path
     * @return self
     */
    public function setStoreFile(string $storeFile): self
    {
        $this->config->setStoreFile($storeFile);
        // Re-instantiate client so TokenManager picks the new path
        $this->client = new ApiClient($this->config);
        return $this;
    }

    /**
     * Optionally enable/disable debug logging at runtime.
     *
     * @param bool $debug
     * @return self
     */
    public function setDebug(bool $debug): self
    {
        $this->config->setDebug($debug);
        // Re-instantiate client so TokenManager sees new debug setting
        $this->client = new ApiClient($this->config);
        return $this;
    }

    /**
     * Set the business code.
     *
     * @param string $businessCode The business code
     *
     * @return self
     */
    public function setBusinessCode(string $businessCode): self
    {
        $this->config->setBusinessCode($businessCode);

        return $this;
    }

    /**
     * Set the pass key.
     *
     * @param string $passKey The pass key
     *
     * @return self
     */
    public function setPassKey(string $passKey): self
    {
        $this->config->setPassKey($passKey);

        return $this;
    }

    /**
     * Set M-Pesa's public key certificate, used to encrypt initiator passwords.
     *
     * @param string $certificate Path to the .cer file from the Daraja portal, or its PEM contents
     *
     * @return self
     */
    public function setCertificate(string $certificate): self
    {
        $this->config->setCertificate($certificate);

        return $this;
    }

    /**
     * Set an already encrypted security credential (e.g. generated on the
     * Daraja portal). With this set, no certificate is needed.
     *
     * @param string $credential The encrypted security credential
     *
     * @return self
     */
    public function setSecurityCredential(string $credential): self
    {
        $this->config->overrideSecurityCredential($credential);

        return $this;
    }

    /**
     * Get STK push service.
     *
     * @return StkService
     */
    public function stk(): StkService
    {
        return new StkService($this->config, $this->client);
    }

    /**
     * Get C2B service.
     *
     * @return CustomerToBusinessService
     */
    public function customerToBusiness(): CustomerToBusinessService
    {
        return new CustomerToBusinessService($this->config, $this->client);
    }

    /**
     * Get B2C service.
     *
     * @return BusinessToCustomerService
     */
    public function businessToCustomer(): BusinessToCustomerService
    {
        return new BusinessToCustomerService($this->config, $this->client);
    }

    /**
     * Get account balance service.
     *
     * @return AccountBalanceService
     */
    public function accountBalance(): AccountBalanceService
    {
        return new AccountBalanceService($this->config, $this->client);
    }

    /**
     * Get transaction status service.
     *
     * @return TransactionStatusService
     */
    public function transactionStatus(): TransactionStatusService
    {
        return new TransactionStatusService($this->config, $this->client);
    }

    /**
     * Get reversal service.
     *
     * @return ReversalService
     */
    public function reversal(): ReversalService
    {
        return new ReversalService($this->config, $this->client);
    }

    /**
     * Get B2B Express Checkout (USSD Push to Till) service.
     *
     * @return B2BExpressCheckoutService
     */
    public function b2bExpressCheckout(): B2BExpressCheckoutService
    {
        return new B2BExpressCheckoutService($this->config, $this->client);
    }

    /**
     * Get Dynamic QR code service.
     *
     * @return DynamicQrService
     */
    public function dynamicQr(): DynamicQrService
    {
        return new DynamicQrService($this->config, $this->client);
    }

    /**
     * Get Tax Remittance (KRA) service.
     *
     * @return TaxRemittanceService
     */
    public function taxRemittance(): TaxRemittanceService
    {
        return new TaxRemittanceService($this->config, $this->client);
    }

    /**
     * Get Pull Transactions service.
     *
     * @return PullTransactionsService
     */
    public function pullTransactions(): PullTransactionsService
    {
        return new PullTransactionsService($this->config, $this->client);
    }

    /**
     * Get Business Pay Bill service.
     *
     * @return BusinessPayBillService
     */
    public function businessPayBill(): BusinessPayBillService
    {
        return new BusinessPayBillService($this->config, $this->client);
    }

    /**
     * Get Business Buy Goods service.
     *
     * @return BusinessBuyGoodsService
     */
    public function businessBuyGoods(): BusinessBuyGoodsService
    {
        return new BusinessBuyGoodsService($this->config, $this->client);
    }

    /**
     * Get B2C Account Top Up service.
     *
     * @return B2CAccountTopUpService
     */
    public function b2cAccountTopUp(): B2CAccountTopUpService
    {
        return new B2CAccountTopUpService($this->config, $this->client);
    }

    /**
     * Get Business to Pochi (B2Pochi) service.
     *
     * @return BusinessToPochiService
     */
    public function businessToPochi(): BusinessToPochiService
    {
        return new BusinessToPochiService($this->config, $this->client);
    }

    /**
     * Get B2C Hakikisha (customer name lookup) service.
     *
     * @return B2CHakikishaService
     */
    public function b2cHakikisha(): B2CHakikishaService
    {
        return new B2CHakikishaService($this->config, $this->client);
    }

    /**
     * Get Mobile Number Validation (KYC) service.
     *
     * @return MobileNumberValidationService
     */
    public function mobileNumberValidation(): MobileNumberValidationService
    {
        return new MobileNumberValidationService($this->config, $this->client);
    }

    /**
     * Get Mobile Data Bundles (Dynamic Offers) service.
     *
     * @return MobileDataBundlesService
     */
    public function mobileDataBundles(): MobileDataBundlesService
    {
        return new MobileDataBundlesService($this->config, $this->client);
    }

    /**
     * Get IoT SIM Management service.
     *
     * @return IotSimService
     */
    public function iotSim(): IotSimService
    {
        return new IotSimService($this->config, $this->client);
    }

    /**
     * Get Age on Network service.
     *
     * @return AgeOnNetworkService
     */
    public function ageOnNetwork(): AgeOnNetworkService
    {
        return new AgeOnNetworkService($this->config, $this->client);
    }

    /**
     * Get Lipa na Bonga service.
     *
     * @return LipaNaBongaService
     */
    public function lipaNaBonga(): LipaNaBongaService
    {
        return new LipaNaBongaService($this->config, $this->client);
    }

    /**
     * Clear the cached auth token. Forces next call to fetch and write to current store file.
     *
     * @return self
     */
    public function clearTokenCache(): self
    {
        (new TokenManager($this->config))->clearCache();
        return $this;
    }

    /**
     * Get the resolved token cache file path used by the current configuration.
     */
    public function getResolvedStoreFilePath(): string
    {
        return (new TokenManager($this->config))->getCacheFilePath();
    }
}
