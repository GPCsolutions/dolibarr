-- Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
-- Copyright (C) 2012      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

INSERT INTO llx_c_serial_type(rowid, code, label, algo_valid, active) values (1, 'imei', 'IMEI', 1, 1);
INSERT INTO llx_c_serial_type(rowid, code, label, algo_valid, active) values (2, 'iccid', 'ICCID (SIM)', 1, 1);
