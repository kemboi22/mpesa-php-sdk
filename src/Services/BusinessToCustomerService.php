<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Business to Customer service.
 */
class BusinessToCustomerService extends AbstractService
{
    private string $initiator_name = "";

    private string $command_id = "";

    private string $remarks = "";

    private string $occasion = "";

    private int $amount = 0;

    private string $phone_number = "";

    /**
     * Sets the username of the M-Pesa API operator.
     *
     * @param string $initiator_name The username of the M-Pesa API operator.
     *
     * @return $this
     */
    public function setInitiatorName(string $initiator_name): self
    {
        $this->initiator_name = $initiator_name;

        return $this;
    }

    /**
     * Sets the command ID.
     *
     * The command ID is a short name that identifies the type of transaction.
     * The following are some of the supported command IDs:
     * - SalaryPayment
     * - BusinessPayment
     * - PromotionPayment
     * - CheckBalance
     * - BuyGoods
     * - DisburseFunds
     * - PayBill
     *
     * @param string $command_id The command ID.
     *
     * @return $this
     */
    public function setCommandId(string $command_id): self
    {
        $this->command_id = $command_id;

        return $this;
    }

    /**
     * Sets the remarks for the transaction.
     *
     * Remarks are additional information or comments associated
     * with the transaction. This information is typically used
     * for record-keeping or auditing purposes.
     *
     * @param string $remarks The remarks for the transaction.
     *
     * @return $this
     */
    public function setRemarks(string $remarks): self
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Sets the occasion for the transaction.
     *
     * The occasion is a descriptive name given to the transaction.
     * Examples of occasions include "Salary Payment", "Business Payment",
     * "Promotion Payment", etc.
     *
     * @param string $occasion The occasion for the transaction.
     *
     * @return $this
     */
    public function setOccasion(string $occasion): self
    {
        $this->occasion = $occasion;

        return $this;
    }

    /**
     * Sends a business to customer payment request to the M-Pesa API.
     *
     * @param string|null $initiator_name     The username of the M-Pesa B2C account API operator.
     * @param string|null $initiator_password The password to authenticate the initiator.
     * @param string|null $command_id         The type of transaction: Either "SalaryPayment", "BusinessPayment", or "PromotionPayment".
     * @param int|null    $amount             The amount of money to be sent.
     * @param string|null $partyA             The shortcode to receive the transaction.
     * @param string|null $phone_number       The customer mobile number to receive the amount.
     * @param string|null $remarks            Any additional information to be associated with the transaction.
     * @param string|null $queue_timeout_url  The URL for timeout notification if the request times out in the queue.
     * @param string|null $result_url         The URL to receive the response from the M-Pesa API.
     * @param string|null $occasion           Any additional information to be associated with the transaction.
     *
     * @return $this Read the result with getResponse()
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function paymentRequest(
        ?string $initiator_name = null,
        ?string $initiator_password = null,
        ?string $command_id = null,
        ?int    $amount = null,
        ?string $partyA = null,
        ?string $phone_number = null,
        ?string $remarks = null,
        ?string $queue_timeout_url = null,
        ?string $result_url = null,
        ?string $occasion = null
    ): self {
        if ($initiator_name !== null) {
            $this->setInitiatorName($initiator_name);
        }
        if ($command_id !== null) {
            $this->setCommandId($command_id);
        }
        if ($amount !== null) {
            $this->setAmount($amount);
        }
        if ($partyA !== null) {
            $this->config->setBusinessCode($partyA);
        }
        if ($phone_number !== null) {
            $this->setPhoneNumber($phone_number);
        }
        if ($remarks !== null) {
            $this->setRemarks($remarks);
        }
        if ($queue_timeout_url !== null) {
            $this->config->setQueueTimeoutUrl($queue_timeout_url);
        }
        if ($result_url !== null) {
            $this->config->setResultUrl($result_url);
        }
        if ($occasion !== null) {
            $this->setOccasion($occasion);
        }
        if ($initiator_password !== null) {
            $this->config->setSecurityCredential($initiator_password);
        }

        $this->validateParams();

        $requestData = [
            "InitiatorName" => $this->initiator_name,
            "SecurityCredential" => $this->config->getSecurityCredential(),
            "CommandID" => $this->command_id,
            "Amount" => $this->amount,
            "PartyA" => $this->config->getBusinessCode(),
            "PartyB" => $this->phone_number,
            "Remarks" => $this->remarks,
            "QueueTimeOutURL" => $this->config->getQueueTimeoutUrl(),
            "ResultURL" => $this->config->getResultUrl(),
            "Occassion" => $this->occasion,
        ];

        $this->response = $this->client->executeRequest($requestData, "/mpesa/b2c/v1/paymentrequest");

        return $this;
    }

    /**
     * Validate required parameters before sending.
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    private function validateParams(): void
    {
        if (empty($this->initiator_name)) {
            throw new \InvalidArgumentException('Initiator name is required');
        }

        if (empty($this->config->getSecurityCredential())) {
            throw new \InvalidArgumentException('Security credential is required');
        }

        if (empty($this->command_id)) {
            throw new \InvalidArgumentException('Command ID is required');
        }

        if ($this->amount < 1) {
            throw new \InvalidArgumentException('Amount is required');
        }

        if (empty($this->config->getBusinessCode())) {
            throw new \InvalidArgumentException('Business code (PartyA) is required');
        }

        if (empty($this->phone_number)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        if (empty($this->config->getQueueTimeoutUrl())) {
            throw new \InvalidArgumentException('Queue timeout URL is required');
        }

        if (empty($this->config->getResultUrl())) {
            throw new \InvalidArgumentException('Result URL is required');
        }
    }

    /**
     * Sets the amount for the transaction.
     *
     * This method assigns a specified amount to the transaction.
     * The amount is expected to be an integer value.
     *
     * @param int $amount The amount to be set for the transaction.
     *
     * @return $this
     */
    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Sets the phone number of the customer.
     *
     * The number is normalised to the 254XXXXXXXXX format.
     *
     * @param string $phone_number The phone number of the customer.
     *
     * @return self
     *
     * @throws \RuntimeException If the phone number is invalid
     */
    public function setPhoneNumber(string $phone_number): self
    {
        $this->phone_number = $this->cleanPhoneNumber($phone_number);

        return $this;
    }
}
