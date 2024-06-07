<?php

namespace DWIPS\Modbus\libs;

class ModBus_Type
{
    const ModBus_TCP = 0;
    const ModBus_UDP = 1;
    const ModBus_RTU = 2;
    const ModBus_RTU_TCP = 3;
    const ModBus_RTU_UDP = 4;
    const ModBus_ASCII = 5;
    const ModBus_ASCII_TCP = 6;
    const ModBus_ASCII_UDP = 7;
}