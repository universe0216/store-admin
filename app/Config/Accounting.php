<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Accounting extends BaseConfig
{
    /** Ledger base / reporting currency. */
    public string $baseCurrency = 'USD';

    /** Inventory asset account (debited on purchase). */
    public string $inventoryAccount = '1200';

    /** Sales revenue account (credited on sale). */
    public string $salesRevenueAccount = '4000';

    /** Cost of goods sold (debited on sale). */
    public string $cogsAccount = '5000';

    /** Bank/wire transfer fees on purchases (expense). */
    public string $transferFeeAccount = '5100';

    /** Cash account (default payment). */
    public string $cashAccount = '1000';

    /** Bank account for non-cash payments. */
    public string $bankAccount = '1010';

    /**
     * Payment methods that credit the bank account instead of cash.
     *
     * @var list<string>
     */
    public array $bankPaymentMethods = ['bank_transfer', 'card', 'check'];

    /**
     * Cash and bank accounts shown on the transactions page.
     *
     * @return list<string>
     */
    public function moneyAccountCodes(): array
    {
        return array_values(array_unique([$this->cashAccount, $this->bankAccount]));
    }
}
