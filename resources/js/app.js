import './bootstrap';

import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

import { attachFabricToWindow } from './lib/fabric-setup';

import './pages/master-requests-create';
import './pages/master-requests-show';
import './pages/master-print-create';
import './pages/oracle-jobs-import';
import './pages/master-print-template';
import './pages/label-requests-create';
import './pages/label-print-center';
import './pages/sku-template-configurations-form';
import './pages/dummy-requests-create';
import './pages/dummy-requests-show';
import './pages/dummy-qr-templates-create';

// opcional: hacerlo global
window.Swal = Swal
attachFabricToWindow();
