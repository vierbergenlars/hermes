/*
 * Hermes, an HTTP-based templated mail sender for transactional and mass mailing.
 *
 * Copyright (C) 2016  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$('.js-acl-container a').on('click', function(ev) {
    var $acl_edit = $('#acl_edit');
    $acl_edit.find('.modal-body').html('<div class="text-center"><span class="fa fa-circle-o-notch fa-spin fa-3x"></span></div>');

    $acl_edit.modal('show');
    $.get($(this).attr('href'), function(data) {
        $acl_edit.find('.modal-body').html(data);
    });
    ev.preventDefault();
});
