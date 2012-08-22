<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2011 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    * ------------------------------------------------------------------------ *                                                                       *
    * This file is part of ALIX.                                               *
    *                                                                          *
    * ALIX is free software: you can redistribute it and/or modify             *
    * it under the terms of the GNU General Public License as published by     *
    * the Free Software Foundation, either version 3 of the License, or        *
    * (at your option) any later version.                                      *
    *                                                                          *
    * ALIX is distributed in the hope that it will be useful,                  *
    * but WITHOUT ANY WARRANTY; without even the implied warranty of           *
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            *
    * GNU General Public License for more details.                             *
    *                                                                          *
    * You should have received a copy of the GNU General Public License        *
    * along with ALIX.  If not, see <http://www.gnu.org/licenses/>.            *
    \**************************************************************************/

	/* $Id: setup.inc.php 20295 2010-08-20 12:31:25Z  $ */

  require_once(dirname(__FILE__). "/../config.inc.php");

	/* Basic information about this app */
	$setup_info[$moduleName]['name']      = $moduleName;
	$setup_info[$moduleName]['title']     = $moduleTitle;
	$setup_info[$moduleName]['version']   = '12.6.0';
	$setup_info[$moduleName]['app_order'] = 100;
	$setup_info[$moduleName]['enable']    = 1;

	/* some info's for about.php and apps.egroupware.org */
	$setup_info[$moduleName]['author']    = 'Business & Decision Life Sciences';
	$setup_info[$moduleName]['license']   = 'GPLV3';
	$setup_info[$moduleName]['description'] = 'ALIX EDC SOLUTIONS';
	$setup_info[$moduleName]['note'] =	'';
	$setup_info[$moduleName]['maintainer'] = 'Willy LAMBERT';
	$setup_info[$moduleName]['maintainer_email'] = 'willy.lambert@businessdecision.com';
	
	$setup_info[$moduleName]['tables'][] = 'egw_alix_acl';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_deviations';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_export';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_export_log';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_import';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_lock';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_postit';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_queries';
	$setup_info[$moduleName]['tables'][] = 'egw_alix_sites';