<?php
/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Api;

interface ShipmentStatusUpdateManagementInterface
{

    /**
     * POST for ShipmentStatusUpdate api
     * @param string $param
     * @return string
     */
    public function postShipmentStatusUpdate();
}
