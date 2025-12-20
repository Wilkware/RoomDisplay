<?php

/**
 * WebhookHelper.php
 *
 * Part of the Trait-Libraray for IP-Symcon Modules.
 *
 * @package       traits
 * @author        Heiko Wilknitz <heiko@wilkware.de>
 * @copyright     2025 Heiko Wilknitz
 * @link          https://wilkware.de
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

declare(strict_types=1);

/**
 * Helper class for web hooks.
 */
trait WebhookHelper
{
    /**
     * Register a new web hook, if not already existing.
     *
     * @param string $hook Path of the web hook.
     *
     * @return bool
     */
    protected function RegisterHook(string $hook): bool
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $key => $value) {
                if ($value['Hook'] == $hook) {
                    if ($value['TargetID'] == $this->InstanceID) {
                        return true;
                    }
                    $hooks[$key]['TargetID'] = $this->InstanceID;
                    $found = true;
                    $this->SendDebug(__FUNCTION__, 'Update hook:' . $hook . $this->InstanceID, 0);
                }
            }
            // New Hook?
            if ($found === false) {
                $hooks[] = ['Hook' => $hook, 'TargetID' => $this->InstanceID];
                $this->SendDebug(__FUNCTION__, 'New hook:' . $hook . $this->InstanceID, 0);
            }
            // Update or Register
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
            return true;
        }
        return false;
    }

    /**
     * Unregister a web hook, if not already existing.
     *
     * @param string $hook Path of the web hook.
     *
     * @return void
     */
    protected function UnregisterHook(string $hook): void
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            foreach ($hooks as $i => $value) {
                if ($value['Hook'] === $hook) {
                    unset($hooks[$i]);
                    $this->SendDebug(__FUNCTION__, $hook . $this->InstanceID, 0);
                    IPS_SetProperty($ids[0], 'Hooks', json_encode(array_values($hooks)));
                    IPS_ApplyChanges($ids[0]);
                    break;
                }
            }
        }
    }
}