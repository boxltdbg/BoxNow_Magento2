<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Elegento\BoxNow\Api;

interface GenerateBoxnowVoucherManagementInterface
{

    /**
     * POST for generateBoxnowVoucher api
     * @param string $orderIncrementId
     * @return mixed
     */
    public function postGenerateBoxnowVoucher($orderIncrementId);
}
