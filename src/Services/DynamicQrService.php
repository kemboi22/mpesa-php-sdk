<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Dynamic QR code service.
 *
 * Generates a QR code that M-Pesa customers scan to pay a merchant.
 */
class DynamicQrService extends AbstractService
{
    /**
     * Supported transaction types.
     * BG: Buy Goods, WA: Withdraw at Agent Till, PB: Paybill,
     * SM: Send Money, SB: Send to Business.
     */
    public const TRX_CODES = ['BG', 'WA', 'PB', 'SM', 'SB'];

    private string $merchantName = '';

    private string $refNo = '';

    private string $amount = '';

    private string $trxCode = '';

    private string $cpi = '';

    private string $size = '300';

    /**
     * Set the merchant name.
     *
     * @param string $merchantName The company/M-Pesa merchant name
     *
     * @return self
     */
    public function setMerchantName(string $merchantName): self
    {
        $this->merchantName = $merchantName;

        return $this;
    }

    /**
     * Set the transaction reference.
     *
     * @param string $refNo The transaction reference
     *
     * @return self
     */
    public function setRefNo(string $refNo): self
    {
        $this->refNo = $refNo;

        return $this;
    }

    /**
     * Set the total amount for the transaction.
     *
     * @param int|string $amount The amount
     *
     * @return self
     */
    public function setAmount($amount): self
    {
        $this->amount = (string)$amount;

        return $this;
    }

    /**
     * Set the transaction type.
     *
     * @param string $trxCode Transaction type (BG|WA|PB|SM|SB)
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the transaction type is not supported
     */
    public function setTrxCode(string $trxCode): self
    {
        $trxCode = strtoupper($trxCode);

        if (! in_array($trxCode, self::TRX_CODES, true)) {
            throw new \InvalidArgumentException(
                'Transaction type must be one of: ' . implode(', ', self::TRX_CODES)
            );
        }

        $this->trxCode = $trxCode;

        return $this;
    }

    /**
     * Set the credit party identifier.
     * A mobile number, till, agent till, paybill or business number.
     * Defaults to the configured business code when not set.
     *
     * @param string $cpi The credit party identifier
     *
     * @return self
     */
    public function setCpi(string $cpi): self
    {
        $this->cpi = $cpi;

        return $this;
    }

    /**
     * Set the QR code image size in pixels (the image is square).
     *
     * @param int|string $size The size in pixels
     *
     * @return self
     */
    public function setSize($size): self
    {
        $this->size = (string)$size;

        return $this;
    }

    /**
     * Validate required parameters before generating.
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    private function validateGenerateParams(): void
    {
        if (empty($this->merchantName)) {
            throw new \InvalidArgumentException('Merchant name is required');
        }

        if (empty($this->refNo)) {
            throw new \InvalidArgumentException('Reference number is required');
        }

        if (empty($this->amount)) {
            throw new \InvalidArgumentException('Amount is required');
        }

        if (empty($this->trxCode)) {
            throw new \InvalidArgumentException('Transaction type is required');
        }

        if (empty($this->cpi)) {
            throw new \InvalidArgumentException('Credit party identifier is required');
        }

        if (empty($this->size)) {
            throw new \InvalidArgumentException('Size is required');
        }
    }

    /**
     * Generate a dynamic QR code.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function generate(): self
    {
        if (empty($this->cpi)) {
            $this->cpi = $this->config->getBusinessCode();
        }

        $this->validateGenerateParams();

        $data = [
            'MerchantName' => $this->merchantName,
            'RefNo' => $this->refNo,
            'Amount' => is_numeric($this->amount) ? $this->amount + 0 : $this->amount,
            'TrxCode' => $this->trxCode,
            'CPI' => $this->cpi,
            'Size' => $this->size,
        ];

        $this->response = $this->client->executeRequest($data, '/mpesa/qrcode/v1/generate');

        return $this;
    }

    /**
     * Get the base64-encoded QR code image from the response.
     *
     * @return string The base64 PNG data
     *
     * @throws \RuntimeException If no QR code is available
     */
    public function getQrCode(): string
    {
        if (! $this->response || empty($this->response->QRCode)) {
            throw new \RuntimeException('No QR code response available');
        }

        return $this->response->QRCode;
    }

    /**
     * Get the QR code as a data URI, ready for an <img src="...">.
     *
     * @return string The data URI
     */
    public function getQrCodeDataUri(): string
    {
        return 'data:image/png;base64,' . $this->getQrCode();
    }

    /**
     * Save the QR code image to a PNG file.
     *
     * @param string $path The file path
     *
     * @return self
     *
     * @throws \RuntimeException If the QR code cannot be decoded or written
     */
    public function saveQrCode(string $path): self
    {
        $image = base64_decode($this->getQrCode(), true);

        if (false === $image) {
            throw new \RuntimeException('QR code is not valid base64 data');
        }

        if (false === file_put_contents($path, $image)) {
            throw new \RuntimeException("Unable to write QR code to $path");
        }

        return $this;
    }
}
