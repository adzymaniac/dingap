<?php

/**
 * NAT firewall summary view.
 *
 * @category   ClearOS
 * @package    NAT_Firewall
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/nat_firewall/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\firewall\Firewall as Firewall;

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('nat_firewall');
$this->lang->load('firewall');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('firewall_nickname'),
    lang('nat_firewall_lan_ip'),
    lang('nat_firewall_wan_ip'),
    lang('firewall_protocol'),
    lang('firewall_port')
);


///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/nat_firewall/add'));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($nat_rules as $rule) {

    // Parse out host info
    list($lan_ip, $wan_ip, $protocol, $port_start, $port_end) = split("\\|", $rule['host']);

    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;
    $key = $rule['name'] . '/' . $wan_ip . '/' . $lan_ip .
        '/' . ($protocol ? $protocol : Firewall::PROTOCOL_ALL) .
        '/' . ($port_start ? $port_start : '0') .
        ($port_end ? ':' . $port_end : '') . '/' .
        $rule['interface'];

    $item['title'] = $rule['name'];
    $item['action'] = '/app/nat_firewall/delete/' . $key;
    $item['anchors'] = button_set(
        array(
            $state_anchor('/app/nat_firewall/' . $state . '/' . $key, 'high'),
            anchor_delete('/app/nat_firewall/delete/' . $key, 'low')
        )
    );


    $item['details'] = array(
        $rule['name'],
        $lan_ip,
        $wan_ip,
        (!$rule['protocol'] ? lang('base_all') : $rule['protocol_name']),
        ($port_start == Firewall::CONSTANT_ALL_PORTS ? lang('base_all') : $port_start) . ($port_end ? ':' . $port_end : '')
    );

    $items[] = $item;
}

sort($items);

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('nat_firewall_rules'),
    $anchors,
    $headers,
    $items
);
