<?php


namespace App\Services\Customer;

use Storage;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Helper;
use setasign\Fpdi\Fpdi;
// use Barryvdh\DomPDF\PDF;
use App\Helpers\SalesHelper;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerService
{
    public function getFormProspekEntry($task_id)
    {
        $meta = [];

        $result = DB::connection('tis_master')->select(
            'SELECT * FROM idmall__customer_activation WHERE Task_ID = ?',
            [$task_id]
        );

        $data = $result ? $result[0] : [];

        return [
            'status' => 'success',
            'meta' => $meta,
            'data' => $data,
        ];
    }

    public function customerEntriDataProspek($body, $auth)
    {
        // Check coverage
        // $is_covered = Helper::isLatLonInsideCoverage($body['latitude'], $body['longitude']);
        // $is_covered = Helper::isLatLonInsideCoverage($body['latitude'], $body['longitude'], 15);

        // if (!$is_covered) {
        //     abort(403, "Mohon maaf, saat ini area anda tidak masuk dalam coverage kami");
        // }

        // Get complementary data
        $region = DB::connection('tis_master')
            ->table('tis_master.master_kodepos_new')
            ->where('ZipCode', $body['zip_code'])
            ->first();

        $product = DB::connection('tis_master')
            ->table('tis_master.produk')
            ->where('Product_Code', $body['product_code'])
            ->first();

        $telesales = DB::connection('tis_master')
            ->table('tis_main.user_l')
            ->where('TipeUser', 'TELESALES')
            ->inRandomOrder()
            ->first();

        if (!$region) {
            abort(404, "Area tidak dalam coverage kami.");
        }
        if (!$product) {
            abort(404, "Product tidak tersedia.");
        }

        // Generate task_id, external_id, phone
        $highest = DB::connection('tis_master')
            ->table('tis_master.customer_activation')
            ->orderByDesc('ID')
            ->first();

        $new_task_id = Helper::createTaskID($highest->Task_ID ?? null);
        // $phone = Helper::convertPhoneNumber($body['phone']);
        $phoneNumbers = Helper::convertPhoneNumber($body['phone']);
        $phone = $phoneNumbers[0] ?? '';
        $external_id = Helper::createExternalID();
        $customer_id = $body['provider_id'] ?? env('RETAIL_CUSTOMER_ID', 1);

        // Insert customer activation
        DB::connection('tis_master')->table('tis_master.customer_activation')->insert([
            'Customer_ID' => $customer_id,
            'Project_ID_By' => $body['project_id'],
            'Project_ID_Date' => Carbon::now('Asia/Jakarta'),
            'RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Pending_Payment_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Freeze_Date' => Carbon::now('Asia/Jakarta'),
            'Freeze_Action' => Carbon::now('Asia/Jakarta'),
            'Start_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'End_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'PO_Date' => Carbon::now('Asia/Jakarta'),
            'Last_Invoiced' => Carbon::now('Asia/Jakarta'),
            'To_Invoice_Backup_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Sub_Name' => $body['fullname'],
            'Customer_Sub_Address' => $body['address'],
            // 'ZipCode' => $body['zip_code'] ?? null,
            'Device_Name' => $body['device_name'],
            'Device_Name2' => $body['device_name2'],
            'Activation_By' => $body['Activation_By'],
            'Sub_Services' => $body['Sub_Services'],
            'Basic_Price' => $body['Basic_Price'],
            'Sub_Product_1' => $body['Sub_Product_1'],
            'Sub_Services_Product' => $body['Sub_Services_Product'],
            'Revenue_Share' => $body['Revenue_Share'],
            'Jenis_Koneksi' => $body['Jenis_Koneksi'],
            'Contract_No' => $body['Contract_No'],
            'BoQ_Desk' => $body['BoQ_Desk'],
            'BoQ_Desk_Jasa' => $body['BoQ_Desk_Jasa'],
            'DRM_BoQ' => $body['DRM_BoQ'],
            'ONU_Serial' => $body['ONU_Serial'],
            'IPTransit_LL' => $body['IPTransit_LL'],
            'Voucher' => $body['Voucher'],
            'AddOn_Monthly_Price' => $body['AddOn_Monthly_Price'],
            'Pending_Payment_Proforma' => $body['Pending_Payment_Proforma'],
            'Pending_Payment_Invoice' => $body['Pending_Payment_Invoice'],
            'District' => $region->District,
            'City' => $region->City,
            'Province' => $region->Province,
            'Customer_Sub_Name2' => $body['fullname'],
            'Customer_Sub_Address2' => $body['address'],
            'Created_Disc_By' => $body['Created_Disc_By'],
            'Last_Invoiced_No' => $body['Last_Invoiced_No'],
            'Request_Change_Price' => $body['Request_Change_Price'],
            'To_Invoice_Backup' => $body['To_Invoice_Backup'],
            'Discount' => $body['Discount'],
            'Estimasi_Disc_Price' => $body['Estimasi_Disc_Price'],
            'Change_Price' => $body['Change_Price'],
            'Estimasi_Change_Price' => $body['Estimasi_Change_Price'],
            'Discount_DU' => $body['Discount_DU'],
            'Estimasi_DU_Price' => $body['Estimasi_DU_Price'],
            'Approval_Discount_1' => $body['Approval_Discount_1'],
            'Approval_Discount_2' => $body['Approval_Discount_2'],
            'Approval_DU_1' => $body['Approval_DU_1'],
            'Approval_DU_2' => $body['Approval_DU_2'],
            'Approval_Change_Price_1' => $body['Approval_Change_Price_1'],
            'Approval_Change_Price_2' => $body['Approval_Change_Price_2'],
            'Acc_Discount' => $body['Acc_Discount'],
            'Periode_Description' => $body['Periode_Description'],
            'PO_No' => $body['PO_No'],
            'SFP_Num' => $body['SFP_Num'],
            'ZipCode2' => $body['zip_code2'],
            'District2' => $region->District,
            'City2' => $region->City,
            'Province2' => $region->Province,
            'Handphone' => $phone,
            'Latitude' => $body['latitude'],
            'Latitude2' => $body['latitude'],
            'Longitude' => $body['longitude'],
            'Longitude2' => $body['longitude'],
            'Longitude_ONU' => $body['longitude_onu'],
            'Latitude_ONU' => $body['latitude_onu'],
            'Est_Ready' => $body['Est_Ready'],
            'Pending_Payment_Invoice_Paid' => $body['Pending_Payment_Invoice_Paid'],
            'Quotation_No_Installation' => $body['Quotation_No_Installation'],
            'Dismantled_Reason' => $body['Dismantled_Reason'],
            'Req_Freeze' => $body['Req_Freeze'],
            'Status_Approval_Free' => '0',
            'auto_invoice' => 0,
            'Sales_Request' => '',
            'Note_Khusus' => '',
            'CID_Layanan' => '',
            'CID_Segment' => '',
            'CID_Regional' => '',
            'CID_Kota' => '',
            'CID_POP' => '',
            'Bukti_PO' => '',
            'Freeze_Reason' => '',
            'Contract_Period' => 0,
            'CID_Seq' => 0,
            'SR_No' => '',
            'Unfreeze_Date' => Carbon::now('Asia/Jakarta'),
            'Verification_Mail' => 0,
            'Verification_Mail_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_Installation_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Mail_Status' => '',
            'Error_Description' => '',
            'Manual_Invoice_Request' => 0,
            'Manual_Invoice_Request_Date' => Carbon::now('Asia/Jakarta'),
            'Password_Riwayat' => 0,
            'Retention_Status' => '',
            'Retention_Discount' => 0,
            'Retention_By' => '',
            'Retention_Date' => Carbon::now('Asia/Jakarta'),
            'Mgt_Services' => '',
            'Notice_Trial' => Carbon::now('Asia/Jakarta'),
            'Payment_Partial' => 0,
            'Pak_Santoso' => 0,
            'Pak_Santoso_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Revolin' => 0,
            'Pak_Revolin_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Yaaro' => 0,
            'Pak_Yaaro_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Rahman' => 0,
            'Pak_Rahman_Date' => Carbon::now('Asia/Jakarta'),
            'Group_Invoice' => '',
            'Approved_Activation' => 0,
            'Approved_By' => '',
            'Approved_Date' => Carbon::now('Asia/Jakarta'),
            'Taken_Promo' => 0,
            'Start_Billing_Lama' => Carbon::now('Asia/Jakarta'),
            'Blast_Email' => 0,
            'Approval_By' => '',
            'Survey_Approval' => 1,
            'Status_Approval_Inquiry' => '',
            'Approval_Inquiry_By' => '',
            'Belum_Prorate' => 0,
            'Verified_By' => '',
            'Konfirmasi_Aktif' => 0,
            'Request_Payment_Check' => 0,
            'Approve_Payment_Check_By' => '',
            'Approve_Payment_Check_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_By' => '',
            'Discount_OTC' => 0,
            'Estimasi_Disc_OTC' => 0,
            'Approval_OTC_1' => '',
            'Approval_OTC_2' => '',
            'Installator' => '',
            'Location_Interconnection' => '',
            'Device_Interconnection' => '',
            'Port_Interconnection' => '',
            'Media_Interconnection' => '',
            'Harga_Original_Aktifasi' => 0,
            'Devices' => '',
            'Group_Task_ID' => $new_task_id,
            'Cycle_Date' => Carbon::now('Asia/Jakarta'),
            'Cross_Connect' => 0,
            'Collocation' => 0,
            'Service' => 0,
            'BOD_ongoing' => 0,
            'Radius_Coverage' => 0,
            'PP_Bypass' => 0,
            'PP_Bypass_Date' => Carbon::now('Asia/Jakarta'),
            'PP_Bypass_By' => '',
            'Actual_Panjang_Kabel' => '',
            'Monthly_Recurring_Collection' => 0,
            'Region' => $region->Regional,
            'E_KTP' => '',
            'Task_ID' => $new_task_id,
            'Email_Customer' => $body['email'] ?? null,
            'Services' => $product->Product_Code,
            'Product_Code' => $product->Product_Code,
            'Status' => env('OSS_PROCESS_STATUS_CREATED', 'CREATED'),
            'Created_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Name' => 'idPlay',
            'Created_By' => $telesales->UserID,
            'Sudah_PO' => 'BELUM',
            'External_ID' => $external_id,
            'Monthly_Price' => $product->Price,
            'Sub_Product' => $product->Product_Name,
            'Sub_Product' => $product->Product_Name,
            'Bandwidth' => $product->Limitation,
            'Data_From' => 'MOBILE_CUSTOMER',
            'Category_Coverage' => 'FAB RFS',
            'Status_Coverage' => 'TERCOVER',
            'Bill_Type' => $body['bill_type'] ?? 'PREPAID-1'
        ]);

        // Insert notes
        DB::connection('tis_master')->table('tis_master.customer_activation_notes')->insert([
            'Customer_ID' => $customer_id,
            'Task_ID' => $new_task_id,
            'Status' => env('OSS_PROCESS_STATUS_CREATED', 'CREATED'),
            'Status_From' => env('OSS_PROCESS_STATUS_CREATED', 'CREATED'),
            'Note' => '[VIA IDMALL] New activation',
            'Created_By' => $telesales->UserID,
            'Created_Date' => Carbon::now('Asia/Jakarta')
        ]);

        return [
            'status' => 'success',
            'message' => 'Berhasil membuat pengajuan berlangganan!',
            'data' => [
                'task_id' => $new_task_id,
                'updated_auth' => Helper::generateToken($auth)
            ]
        ];
    }
    // public function customerEntriDataProspek($body)
    // {
    //     // Logic untuk menyimpan entri data prospek retail
    // }

    public function idplayEntriDataProspek(array $body, array $qs, $auth = null)
    {
        /* ================= SESSION ================= */
        $emailCreatedBy = $auth->email ?? null;
        $externalId = Helper::createExternalID();

        /* ================= REGION ================= */
        $fullAddress = DB::connection('tis_master')
            ->table('tis_master.master_kodepos')
            ->where('ZipCode', 'like', '%' . $body['zip_code'] . '%')
            ->first();

        if (!$fullAddress) {
            abort(404, 'Zipcode tidak ditemukan');
        }

        /* ================= PRODUCT ================= */
        $product = DB::connection('tis_master')
            ->table('tis_master.produk')
            ->where('Product_Code', $body['product_code'])
            ->first();

        if (!$product) {
            abort(404, 'Produk tidak ditemukan');
        }

        /* ================= REFERRAL ================= */
        $referralCode = null;
        $referredBy = null;

        if (!empty($body['referral_code'])) {
            $partner = DB::connection('tis_master')
                ->table('tis_main.user_l')
                ->where('Referral_Code', $body['referral_code'])
                ->where('Status', 'ACTIVE')
                ->first();

            $sales = DB::connection('tis_master')
                ->table('tis_main.user_l')
                ->where('UserID', $body['sales_id'] ?? null)
                ->where('Status', 'ACTIVE')
                ->first();

            $referralCode = $partner->UserID ?? null;
            $referredBy   = $sales->UserID ?? null;
        }

        /* ================= TASK ID ================= */
        $ossHighest = DB::connection('tis_master')
            ->table('tis_master.customer_activation')
            ->max('Task_ID');

        $idmallCount = DB::connection('tis_master')
            ->table('tis_master.idmall__customer_activation')
            ->count();

        $taskId = Helper::createTaskID(($ossHighest ?? 0) + $idmallCount);

        /* ================= NAME ================= */
        $fullname = !empty($body['last_name'])
            ? trim($body['first_name'] . ' ' . $body['last_name'])
            : $body['fullname'];

        /* ================= PHONE ================= */
        $phone = ltrim($body['phone'], '+');
        // $projectId = $body['project_id']
        // ?? 'IDPLAY/' . date('Ym'); //
        $now = Carbon::now('Asia/Jakarta');

        $projectId = $body['project_id'] ?? 'IDPLAY/' . date('Ym');
        $customer_id = $body['provider_id'] ?? env('RETAIL_CUSTOMER_ID', 1);
        $taskId = Helper::createTaskID(($ossHighest ?? 0) + $idmallCount);
        $projectIdBy = $emailCreatedBy ?? 'SYSTEM';
        $projectIdDate = $now;
        /* ================= INSERT ================= */
        DB::connection('tis_master')->table('tis_master.customer_activation')->insert([
            'Task_ID' => $taskId,
            'Group_Task_ID' => $taskId,
            'Customer_ID' => $customer_id,
            'Project_ID_By' => $body['project_id'],
            'Project_ID_Date' => Carbon::now('Asia/Jakarta'),
            'RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Pending_Payment_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Freeze_Date' => Carbon::now('Asia/Jakarta'),
            'Freeze_Action' => Carbon::now('Asia/Jakarta'),
            'Start_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'End_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'PO_Date' => Carbon::now('Asia/Jakarta'),
            'Last_Invoiced' => Carbon::now('Asia/Jakarta'),
            'To_Invoice_Backup_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Sub_Name' => $body['fullname'],
            'Customer_Sub_Address' => $body['address'],
            // 'ZipCode' => $body['zip_code'] ?? null,
            'Device_Name' => $body['device_name'],
            'Device_Name2' => $body['device_name2'],
            'Activation_By' => $body['Activation_By'],
            'Sub_Services' => $body['Sub_Services'],
            'Basic_Price' => $body['Basic_Price'],
            'Sub_Product_1' => $body['Sub_Product_1'],
            'Sub_Services_Product' => $body['Sub_Services_Product'],
            'Revenue_Share' => $body['Revenue_Share'],
            'Jenis_Koneksi' => $body['Jenis_Koneksi'],
            'Contract_No' => $body['Contract_No'],
            'BoQ_Desk' => $body['BoQ_Desk'],
            'BoQ_Desk_Jasa' => $body['BoQ_Desk_Jasa'],
            'DRM_BoQ' => $body['DRM_BoQ'],
            'ONU_Serial' => $body['ONU_Serial'],
            'IPTransit_LL' => $body['IPTransit_LL'],
            'Voucher' => $body['Voucher'],
            'AddOn_Monthly_Price' => $body['AddOn_Monthly_Price'],
            'Pending_Payment_Proforma' => $body['Pending_Payment_Proforma'],
            'Pending_Payment_Invoice' => $body['Pending_Payment_Invoice'],
            // 'District' => $region->District,
            // 'City' => $region->City,
            // 'Province' => $region->Province,
            'Customer_Sub_Name2' => $body['fullname'],
            'Customer_Sub_Address2' => $body['address'],
            'Created_Disc_By' => $body['Created_Disc_By'],
            'Last_Invoiced_No' => $body['Last_Invoiced_No'],
            'Request_Change_Price' => $body['Request_Change_Price'],
            'To_Invoice_Backup' => $body['To_Invoice_Backup'],
            'Discount' => $body['Discount'],
            'Estimasi_Disc_Price' => $body['Estimasi_Disc_Price'],
            'Change_Price' => $body['Change_Price'],
            'Estimasi_Change_Price' => $body['Estimasi_Change_Price'],
            'Discount_DU' => $body['Discount_DU'],
            'Estimasi_DU_Price' => $body['Estimasi_DU_Price'],
            'Approval_Discount_1' => $body['Approval_Discount_1'],
            'Approval_Discount_2' => $body['Approval_Discount_2'],
            'Approval_DU_1' => $body['Approval_DU_1'],
            'Approval_DU_2' => $body['Approval_DU_2'],
            'Approval_Change_Price_1' => $body['Approval_Change_Price_1'],
            'Approval_Change_Price_2' => $body['Approval_Change_Price_2'],
            'Acc_Discount' => $body['Acc_Discount'],
            'Periode_Description' => $body['Periode_Description'],
            'PO_No' => $body['PO_No'],
            'SFP_Num' => $body['SFP_Num'],
            'ZipCode2' => $body['zip_code2'],
            // 'District2' => $region->District,
            // 'City2' => $region->City,
            // 'Province2' => $region->Province,
            'Handphone' => $phone,
            'Latitude' => $body['latitude'],
            'Latitude2' => $body['latitude'],
            'Longitude' => $body['longitude'],
            'Longitude2' => $body['longitude'],
            'Longitude_ONU' => $body['longitude_onu'],
            'Latitude_ONU' => $body['latitude_onu'],
            'Est_Ready' => $body['Est_Ready'],
            'Pending_Payment_Invoice_Paid' => $body['Pending_Payment_Invoice_Paid'],
            'Quotation_No_Installation' => $body['Quotation_No_Installation'],
            'Dismantled_Reason' => $body['Dismantled_Reason'],
            'Req_Freeze' => $body['Req_Freeze'],
            'Status_Approval_Free' => '0',
            'auto_invoice' => 0,
            'Sales_Request' => '',
            'Note_Khusus' => '',
            'CID_Layanan' => '',
            'CID_Segment' => '',
            'CID_Regional' => '',
            'CID_Kota' => '',
            'CID_POP' => '',
            'Bukti_PO' => '',
            'Freeze_Reason' => '',
            'Contract_Period' => 0,
            'CID_Seq' => 0,
            'SR_No' => '',
            'Unfreeze_Date' => Carbon::now('Asia/Jakarta'),
            'Verification_Mail' => 0,
            'Verification_Mail_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_Installation_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Mail_Status' => '',
            'Error_Description' => '',
            'Manual_Invoice_Request' => 0,
            'Manual_Invoice_Request_Date' => Carbon::now('Asia/Jakarta'),
            'Password_Riwayat' => 0,
            'Retention_Status' => '',
            'Retention_Discount' => 0,
            'Retention_By' => '',
            'Retention_Date' => Carbon::now('Asia/Jakarta'),
            'Mgt_Services' => '',
            'Notice_Trial' => Carbon::now('Asia/Jakarta'),
            'Payment_Partial' => 0,
            'Pak_Santoso' => 0,
            'Pak_Santoso_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Revolin' => 0,
            'Pak_Revolin_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Yaaro' => 0,
            'Pak_Yaaro_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Rahman' => 0,
            'Pak_Rahman_Date' => Carbon::now('Asia/Jakarta'),
            'Group_Invoice' => '',
            'Approved_Activation' => 0,
            'Approved_By' => '',
            'Approved_Date' => Carbon::now('Asia/Jakarta'),
            'Taken_Promo' => 0,
            'Start_Billing_Lama' => Carbon::now('Asia/Jakarta'),
            'Blast_Email' => 0,
            'Approval_By' => '',
            'Survey_Approval' => 1,
            'Status_Approval_Inquiry' => '',
            'Approval_Inquiry_By' => '',
            'Belum_Prorate' => 0,
            'Verified_By' => '',
            'Konfirmasi_Aktif' => 0,
            'Request_Payment_Check' => 0,
            'Approve_Payment_Check_By' => '',
            'Approve_Payment_Check_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_By' => '',
            'Discount_OTC' => 0,
            'Estimasi_Disc_OTC' => 0,
            'Approval_OTC_1' => '',
            'Approval_OTC_2' => '',
            'Installator' => '',
            'Location_Interconnection' => '',
            'Device_Interconnection' => '',
            'Port_Interconnection' => '',
            'Media_Interconnection' => '',
            'Harga_Original_Aktifasi' => 0,
            'Devices' => '',
            'Group_Task_ID' => $taskId,
            'Cycle_Date' => Carbon::now('Asia/Jakarta'),
            'Cross_Connect' => 0,
            'Collocation' => 0,
            'Service' => 0,
            'BOD_ongoing' => 0,
            'Radius_Coverage' => 0,
            'PP_Bypass' => 0,
            'PP_Bypass_Date' => Carbon::now('Asia/Jakarta'),
            'PP_Bypass_By' => '',
            'Actual_Panjang_Kabel' => '',
            'Monthly_Recurring_Collection' => 0,
            // 'Region' => $region->Regional,
            'E_KTP' => '',
            'Task_ID' => $taskId,
            'Email_Customer' => $body['email'] ?? null,
            'Services' => $product->Product_Code,
            'Product_Code' => $product->Product_Code,
            'Status' => env('OSS_PROCESS_STATUS_CREATED', 'CREATED'),
            'Created_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Name' => 'idPlay',
            'Created_By' => $telesales->UserID,
            'Sudah_PO' => 'BELUM',
            'External_ID' => $external_id,
            'Monthly_Price' => $product->Price,
            'Sub_Product' => $product->Product_Name,
            'Sub_Product' => $product->Product_Name,
            'Bandwidth' => $product->Limitation,
            'Data_From' => 'MOBILE_CUSTOMER',
            'Category_Coverage' => 'FAB RFS',
            'Status_Coverage' => 'TERCOVER',
            'Bill_Type' => $body['bill_type'] ?? 'PREPAID-1'
        ]);
        return [
            'status' => 'success',
            'message' => 'berhasil membuat pengajuan form entri prospek'
        ];
    }

    public function referralEntriDataProspek($body)
    {
        $body = \App\Helpers\Helper::trimObject($body);

        $emailCreatedBy = auth()->user()->email ?? null;
        $externalId = \App\Helpers\Helper::createExternalID();

        $partner = DB::connection('tis_master')
            ->table('tis_main.user_l')
            ->where('Referral_Code', $body['referral_code'])
            ->where('Status', 'ACTIVE')
            ->first();

        $sales = DB::connection('tis_master')
            ->table('tis_main.user_l')
            ->where('UserID', $body['sales_id'] ?? 0)
            ->where('Status', 'ACTIVE')
            ->first();

        if (!$partner || !$sales) {
            abort(403, 'Tidak diizinkan untuk melakukan input lead data! Kode referral telah hangus');
        }

        $countCustomerActivation = DB::connection('tis_master')->table('customer_activation')->count();
        $countIdmallCustomerActivation = DB::connection('tis_master')->table('idmall__customer_activation')->count();
        $ossHighest = DB::connection('tis_master')->table('customer_activation')->max('Task_ID');

        $taskId = \App\Helpers\Helper::createTaskID(($ossHighest ?? 0) + $countIdmallCustomerActivation);

        $fullAddress = DB::connection('tis_master')
            ->table('master_kodepos')
            ->where('ZipCode', 'like', '%' . $body['zip_code'] . '%')
            ->first();

        if (!$fullAddress) {
            abort(404, 'Zipcode tidak ditemukan');
        }

        $product = DB::connection('tis_master')
            ->table('produk')
            ->where('Product_Code', $body['product_code'])
            ->first();

        if (!$product) {
            abort(404, 'Produk tidak ditemukan');
        }

        $bandwidth = \App\Helpers\Helper::getBandwidthFromProductName(['product_name' => $product->Product_Name]);
        $projectId = $body['project_id'] ?? 'IDPLAY/' . date('Ym');

        DB::connection('tis_master')->table('idmall__customer_activation')->insert([
            'Customer_ID' => $body['provider_id'] ?? env('RETAIL_CUSTOMER_ID', 1),
            'Task_ID' => $taskId,
            'Project_ID' => $body['project_id'] ?? 'IDPLAY/' . date('Ym'),
            'Project_ID_By' => $body['project_id'],
            'Project_ID_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Freeze_Date' => Carbon::now('Asia/Jakarta'),
            'Freeze_Action' => Carbon::now('Asia/Jakarta'),
            'Start_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'End_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'PO_Date' => Carbon::now('Asia/Jakarta'),
            'Last_Invoiced' => Carbon::now('Asia/Jakarta'),
            'To_Invoice_Backup_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Sub_Name' => $body['fullname'],
            'Customer_Sub_Address' => $body['address'],
            'ZipCode' => $body['zip_code'],
            'District' => $fullAddress->District,
            'City' => $fullAddress->City,
            'Province' => $fullAddress->Province,
            'Email_Customer' => $body['email'] ?? null,
            'Handphone' => ltrim($body['phone'], '+'),
            'Services' => $product->Product_Code,
            'Sub_Product' => $product->Product_Name,
            'Monthly_Price' => $product->Price,
            'Bandwidth' => $bandwidth,
            'Referral_Code' => $body['referral_code'],
            'Referred_By' => $sales->UserID,
            'Created_By' => $partner->UserID,
            'External_ID' => $externalId,
            'Status' => env('OSS_PROCESS_STATUS_CREATED', 'CREATED'),
            'Created_Date' => now(),
            'Device_Name' => $body['device_name'],
            'Device_Name2' => $body['device_name2'],
            'Activation_By' => $body['Activation_By'],
            'Sub_Services' => $body['Sub_Services'],
            'Basic_Price' => $body['Basic_Price'],
            'Sub_Product_1' => $body['Sub_Product_1'],
            'Sub_Services_Product' => $body['Sub_Services_Product'],
            'Revenue_Share' => $body['Revenue_Share'],
            'Jenis_Koneksi' => $body['Jenis_Koneksi'],
            'Contract_No' => $body['Contract_No'],
            'BoQ_Desk' => $body['BoQ_Desk'],
            'BoQ_Desk_Jasa' => $body['BoQ_Desk_Jasa'],
            'DRM_BoQ' => $body['DRM_BoQ'],
            'ONU_Serial' => $body['ONU_Serial'],
            'IPTransit_LL' => $body['IPTransit_LL'],
            'Voucher' => $body['Voucher'],
            'AddOn_Monthly_Price' => $body['AddOn_Monthly_Price'],
            'Pending_Payment_Proforma' => $body['Pending_Payment_Proforma'],
            'Pending_Payment_Invoice' => $body['Pending_Payment_Invoice'],
            'RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Pending_Payment_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Sub_Name2' => $body['fullname'],
            'Customer_Sub_Address2' => $body['address'],
            'Created_Disc_By' => $body['Created_Disc_By'],
            'Last_Invoiced_No' => $body['Last_Invoiced_No'],
            'Request_Change_Price' => $body['Request_Change_Price'],
            'To_Invoice_Backup' => $body['To_Invoice_Backup'],
            'Discount' => $body['Discount'],
            'Estimasi_Disc_Price' => $body['Estimasi_Disc_Price'],
            'Change_Price' => $body['Change_Price'],
            'Estimasi_Change_Price' => $body['Estimasi_Change_Price'],
            'Discount_DU' => $body['Discount_DU'],
            'Estimasi_DU_Price' => $body['Estimasi_DU_Price'],
            'Approval_Discount_1' => $body['Approval_Discount_1'],
            'Approval_Discount_2' => $body['Approval_Discount_2'],
            'Approval_DU_1' => $body['Approval_DU_1'],
            'Approval_DU_2' => $body['Approval_DU_2'],
            'Approval_Change_Price_1' => $body['Approval_Change_Price_1'],
            'Approval_Change_Price_2' => $body['Approval_Change_Price_2'],
            'Acc_Discount' => $body['Acc_Discount'],
            'Periode_Description' => $body['Periode_Description'],
            'PO_No' => $body['PO_No'],
            'SFP_Num' => $body['SFP_Num'],
            'ZipCode2' => $body['zip_code2'],
            'Latitude' => $body['latitude'],
            'Latitude2' => $body['latitude'],
            'Longitude' => $body['longitude'],
            'Longitude2' => $body['longitude'],
            'Longitude_ONU' => $body['longitude_onu'],
            'Latitude_ONU' => $body['latitude_onu'],
            'Est_Ready' => $body['Est_Ready'],
            'Pending_Payment_Invoice_Paid' => $body['Pending_Payment_Invoice_Paid'],
            'Quotation_No_Installation' => $body['Quotation_No_Installation'],
            'Dismantled_Reason' => $body['Dismantled_Reason'],
            'Req_Freeze' => $body['Req_Freeze'],
            'Status_Approval_Free' => '0',
            'auto_invoice' => 0,
            'Sales_Request' => '',
            'Note_Khusus' => '',
            'CID_Layanan' => '',
            'CID_Segment' => '',
            'CID_Regional' => '',
            'CID_Kota' => '',
            'CID_POP' => '',
            'Bukti_PO' => '',
            'Contract_Period' => 0,
            'CID_Seq' => 0,
            'SR_No' => '',
            'Unfreeze_Date' => Carbon::now('Asia/Jakarta'),
            'Verification_Mail' => 0,
            'Verification_Mail_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_Installation_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Mail_Status' => '',
            'Error_Description' => '',
            'Manual_Invoice_Request' => 0,
            'Manual_Invoice_Request_Date' => Carbon::now('Asia/Jakarta'),
            'Password_Riwayat' => 0,
            'Retention_Status' => '',
            'Retention_Discount' => 0,
            'Retention_By' => '',
            'Retention_Date' => Carbon::now('Asia/Jakarta'),
            'Mgt_Services' => '',
            'Notice_Trial' => Carbon::now('Asia/Jakarta'),
            'Payment_Partial' => 0,
            'Pak_Santoso' => 0,
            'Pak_Santoso_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Revolin' => 0,
            'Pak_Revolin_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Yaaro' => 0,
            'Pak_Yaaro_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Rahman' => 0,
            'Pak_Rahman_Date' => Carbon::now('Asia/Jakarta'),
            'Group_Invoice' => '',
            'Approved_Activation' => 0,
            'Approved_By' => '',
            'Approved_Date' => Carbon::now('Asia/Jakarta'),
            'Taken_Promo' => 0,
            'Start_Billing_Lama' => Carbon::now('Asia/Jakarta'),
            'Blast_Email' => 0,
            'Approval_By' => '',
            'Survey_Approval' => 1,
            'Status_Approval_Inquiry' => '',
            'Approval_Inquiry_By' => '',
            'Belum_Prorate' => 0,
            'Verified_By' => '',
            'Konfirmasi_Aktif' => 0,
            'Request_Payment_Check' => 0,
            'Approve_Payment_Check_By' => '',
            'Approve_Payment_Check_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_By' => '',
            'Discount_OTC' => 0,
            'Estimasi_Disc_OTC' => 0,
            'Approval_OTC_1' => '',
            'Approval_OTC_2' => '',
            'Installator' => '',
            'Location_Interconnection' => '',
            'Device_Interconnection' => '',
            'Port_Interconnection' => '',
            'Media_Interconnection' => '',
            'Harga_Original_Aktifasi' => 0,
            'Devices' => '',
            'E_KTP' => '',
            'Product_Code' => $product->Product_Code,

        ]);

        return [
            'status' => 'success',
            'message' => 'berhasil membuat pengajuan form entri prospek'
        ];
    }


    public function getLeadCustomer(Request $request): array
    {
        /** ================= SESSION ================= */
        $session = Helper::getAuthorizationTokenData($request);

        if (!$session) {
            abort(401, 'Unauthorized');
        }

        $userId = $session['user_id'] ?? null;

        if (!$userId) {
            abort(401, 'Unauthorized: user_id missing');
        }


        $user = User::find($userId);

        if (!$user) {
            abort(404, 'User not found');
        }

        $username = $user->username;
        $region   = $user->region ?? 'ALL';
        $position = $user->position ?? null;
        /** ================= SALES ================= */
        $sales = SalesHelper::getSalesPositionList([
            'username' => $username,
            'region'   => $region,
        ]);

        $kumpulanSales = array_merge($sales['data'] ?? [], [$username]);

        /** ================= QUERY ================= */
        $query = DB::connection('tis_master')
            ->table('idmall__customer_activation')
            ->whereIn('Status', ['CREATED', 'CHECKING'])
            ->orderByDesc('ID');

        // ===== ROLE KAREG / SM =====
        if (in_array($position, ['KAREG', 'SM'])) {
            $query->where('Region', 'LIKE', $region)
                ->where('Created_By', 'LIKE', '%@%');
        }

        // ===== ROLE SPV / AM =====
        if (in_array($position, ['SPV', 'AM'])) {
            $query->where('Assign_To', $username);
        }

        // ===== ROLE CSV =====
        if ($position === 'CSV') {
            $query->where('Created_By', $username);
        }

        $leads = $query->get();

        /** ================= RESPONSE ================= */
        $data = $leads->map(function ($e) {
            return [
                'id'            => $e->ID,
                'task_id'       => $e->Task_ID,
                'provider_id'   => $e->Customer_ID,
                'provider_name' => $e->Customer_Name,
                'name'          => $e->Customer_Sub_Name,
                'service'       => $e->Sub_Product,
                'created_by'    => $e->Created_By,
                'referred_by'   => $e->Referred_By,
                'created_date'  => $e->Created_Date,
                'assign_to'     => $e->Assign_To,
                'assign_by'     => $e->Assign_By,
                'referral_code' => $e->Referral_Code,
                'data_from'     => $e->Input_From,
                'region'        => $e->Region,
                'status'        => $e->Status,
            ];
        });

        return [
            'message' => 'success',
            'data'    => $data,
        ];
    }

    public function pushLeadCustomer($body)
    {
        $taskID = $body['task_id'];

        // Mendefinisikan field yang diperlukan
        $prospekField = [
            "Customer_ID", "Customer_Sub_Name", "Customer_Sub_Address", "ZipCode",
            "District", "City", "Province", "Customer_Sub_Name2", "Customer_Sub_Address2",
            "ZipCode2", "District2", "City2", "Province2", "Handphone", "Latitude",
            "Latitude2", "Longitude", "Longitude2", "Region", "E_KTP", "Task_ID",
            "Email_Customer", "Services", "Status", "Created_Date", "Customer_Name",
            "Created_By", "Sudah_PO", "External_ID", "Monthly_Price", "Sub_Product",
            "Bandwidth", "Plan_Status", "Referral_Code", "Referred_By", "Data_From"
        ];

        // Ambil data lead customer berdasarkan Task_ID
        $getLead = DB::table('tis_master.idmall__customer_activation')
            ->where('Task_ID', $taskID)
            ->first();

        // Ambil Task_ID terbaru dari tabel customer_activation
        $newTaskID = DB::table('tis_master.customer_activation')
            ->max('Task_ID') + 1;
        $projectIdBy = $getLead->Project_ID_By ?? 'DefaultValue';
        // Insert data ke tabel customer_activation
        DB::table('tis_master.customer_activation')->insert([
            'Customer_ID' => $getLead->Customer_ID,
            'Project_ID_By' => $projectIdBy,
            'Project_ID_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Freeze_Date' => Carbon::now('Asia/Jakarta'),
            'Freeze_Action' => Carbon::now('Asia/Jakarta'),
            'Start_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'End_Date_RFL' => Carbon::now('Asia/Jakarta'),
            'PO_Date' => Carbon::now('Asia/Jakarta'),
            'Last_Invoiced' => Carbon::now('Asia/Jakarta'),
            'To_Invoice_Backup_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Sub_Name' => $body['fullname'],
            'Customer_Sub_Address' => $body['address'],
            'ZipCode' => $body['zip_code'],
            // 'District' => $fullAddress->District,
            // 'City' => $fullAddress->City,
            // 'Province' => $fullAddress->Province,
            'Email_Customer' => $body['email'] ?? null,
            'Handphone' => ltrim($body['phone'], '+'),
            'Services' => $getLead->Product_Code,
            // 'Sub_Product' => $getLead->Product_Name,
            // 'Monthly_Price' => $getLead->Price,
            // 'Bandwidth' => $bandwidth,
            'Referral_Code' => $body['referral_code'],
            // 'Referred_By' => $body->UserID,
            // 'Created_By' => $partner->UserID,
            // 'External_ID' => $externalId,
            'Status' => env('OSS_PROCESS_STATUS_CREATED', 'CREATED'),
            'Created_Date' => now(),
            'Device_Name' => $body['device_name'],
            'Device_Name2' => $body['device_name2'],
            'Activation_By' => $body['Activation_By'],
            'Sub_Services' => $body['Sub_Services'],
            'Basic_Price' => $body['Basic_Price'],
            'Task_ID' => $newTaskID,
            'Freeze_Reason' => '',
            'Group_Task_ID' => '',
            'Sub_Product_1' => $body['Sub_Product_1'],
            'Sub_Services_Product' => $body['Sub_Services_Product'],
            'Revenue_Share' => $body['Revenue_Share'],
            'Jenis_Koneksi' => $body['Jenis_Koneksi'],
            'Contract_No' => $body['Contract_No'],
            'BoQ_Desk' => $body['BoQ_Desk'],
            'BoQ_Desk_Jasa' => $body['BoQ_Desk_Jasa'],
            'DRM_BoQ' => $body['DRM_BoQ'],
            'ONU_Serial' => $body['ONU_Serial'],
            'IPTransit_LL' => $body['IPTransit_LL'],
            'Voucher' => $body['Voucher'],
            'AddOn_Monthly_Price' => $body['AddOn_Monthly_Price'],
            'Pending_Payment_Proforma' => $body['Pending_Payment_Proforma'],
            'Pending_Payment_Invoice' => $body['Pending_Payment_Invoice'],
            'RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Pending_Payment_Date' => Carbon::now('Asia/Jakarta'),
            'Customer_Sub_Name2' => $body['fullname'],
            'Customer_Sub_Address2' => $body['address'],
            'Created_Disc_By' => $body['Created_Disc_By'],
            'Last_Invoiced_No' => $body['Last_Invoiced_No'],
            'Request_Change_Price' => $body['Request_Change_Price'],
            'To_Invoice_Backup' => $body['To_Invoice_Backup'],
            'Discount' => $body['Discount'],
            'Estimasi_Disc_Price' => $body['Estimasi_Disc_Price'],
            'Change_Price' => $body['Change_Price'],
            'Estimasi_Change_Price' => $body['Estimasi_Change_Price'],
            'Discount_DU' => $body['Discount_DU'],
            'Estimasi_DU_Price' => $body['Estimasi_DU_Price'],
            'Approval_Discount_1' => $body['Approval_Discount_1'],
            'Approval_Discount_2' => $body['Approval_Discount_2'],
            'Approval_DU_1' => $body['Approval_DU_1'],
            'Approval_DU_2' => $body['Approval_DU_2'],
            'Approval_Change_Price_1' => $body['Approval_Change_Price_1'],
            'Approval_Change_Price_2' => $body['Approval_Change_Price_2'],
            'Acc_Discount' => $body['Acc_Discount'],
            'Periode_Description' => $body['Periode_Description'],
            'PO_No' => $body['PO_No'],
            'SFP_Num' => $body['SFP_Num'],
            'ZipCode2' => $body['zip_code2'],
            'Latitude' => $body['latitude'],
            'Latitude2' => $body['latitude'],
            'Longitude' => $body['longitude'],
            'Longitude2' => $body['longitude'],
            'Longitude_ONU' => $body['longitude_onu'],
            'Latitude_ONU' => $body['latitude_onu'],
            'Est_Ready' => $body['Est_Ready'],
            'Pending_Payment_Invoice_Paid' => $body['Pending_Payment_Invoice_Paid'],
            'Quotation_No_Installation' => $body['Quotation_No_Installation'],
            'Dismantled_Reason' => $body['Dismantled_Reason'],
            'Req_Freeze' => $body['Req_Freeze'],
            'Status_Approval_Free' => '0',
            'auto_invoice' => 0,
            'Sales_Request' => '',
            'Note_Khusus' => '',
            'CID_Layanan' => '',
            'CID_Segment' => '',
            'CID_Regional' => '',
            'CID_Kota' => '',
            'CID_POP' => '',
            'Bukti_PO' => '',
            'Contract_Period' => 0,
            'CID_Seq' => 0,
            'SR_No' => '',
            'Cycle_Date' => Carbon::now('Asia/Jakarta'),
            'Cross_Connect' => 0,
            'Collocation' => 0,
            'Service' => 0,
            'BOD_ongoing' => 0,
            'Radius_Coverage' => 0,
            'PP_Bypass' => 0,
            'PP_Bypass_Date' => Carbon::now('Asia/Jakarta'),
            'PP_Bypass_By' => '',
            'Actual_Panjang_Kabel' => '',
            'Monthly_Recurring_Collection' => 0,
            'Unfreeze_Date' => Carbon::now('Asia/Jakarta'),
            'Verification_Mail' => 0,
            'Verification_Mail_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_Installation_Date' => Carbon::now('Asia/Jakarta'),
            'Req_Mail_Status' => '',
            'Error_Description' => '',
            'Manual_Invoice_Request' => 0,
            'Manual_Invoice_Request_Date' => Carbon::now('Asia/Jakarta'),
            'Password_Riwayat' => 0,
            'Retention_Status' => '',
            'Retention_Discount' => 0,
            'Retention_By' => '',
            'Retention_Date' => Carbon::now('Asia/Jakarta'),
            'Mgt_Services' => '',
            'Notice_Trial' => Carbon::now('Asia/Jakarta'),
            'Payment_Partial' => 0,
            'Pak_Santoso' => 0,
            'Pak_Santoso_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Revolin' => 0,
            'Pak_Revolin_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Yaaro' => 0,
            'Pak_Yaaro_Date' => Carbon::now('Asia/Jakarta'),
            'Pak_Rahman' => 0,
            'Pak_Rahman_Date' => Carbon::now('Asia/Jakarta'),
            'Group_Invoice' => '',
            'Approved_Activation' => 0,
            'Approved_By' => '',
            'Approved_Date' => Carbon::now('Asia/Jakarta'),
            'Taken_Promo' => 0,
            'Start_Billing_Lama' => Carbon::now('Asia/Jakarta'),
            'Blast_Email' => 0,
            'Approval_By' => '',
            'Survey_Approval' => 1,
            'Status_Approval_Inquiry' => '',
            'Approval_Inquiry_By' => '',
            'Belum_Prorate' => 0,
            'Verified_By' => '',
            'Konfirmasi_Aktif' => 0,
            'Request_Payment_Check' => 0,
            'Approve_Payment_Check_By' => '',
            'Approve_Payment_Check_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_Date' => Carbon::now('Asia/Jakarta'),
            'Estimasi_RFS_By' => '',
            'Discount_OTC' => 0,
            'Estimasi_Disc_OTC' => 0,
            'Approval_OTC_1' => '',
            'Approval_OTC_2' => '',
            'Installator' => '',
            'Location_Interconnection' => '',
            'Device_Interconnection' => '',
            'Port_Interconnection' => '',
            'Media_Interconnection' => '',
            'Harga_Original_Aktifasi' => 0,
            'Devices' => '',
            'E_KTP' => '',
            'Product_Code' => $getLead->Product_Code,
        ]);

        // Update status Task_ID yang lama menjadi "CLOSED"
        DB::table('tis_master.idmall__customer_activation')
            ->where('Task_ID', $taskID)
            ->update(['Status' => 'CLOSED']);

        // Insert catatan ke tabel customer_activation_notes
        DB::table('tis_master.customer_activation_notes')->insert([
            'Customer_ID' => $getLead->Customer_ID,
            'Task_ID' => $newTaskID,
            'Status' => 'CREATED',
            'Status_From' => '',
            'Note' => $body['note'] ?? '',
            'Created_By' => substr(trim($getLead->Created_By), 0, 50),
            'Created_Date' => Carbon::now(),
        ]);

        return [
            'status' => 'success',
            'message' => 'Input Success!',
        ];
    }

    public function previewFAB($body, $session)
    {
        // Ambil data dari $body dan $session
        $fields = $body['fields'];  // Mengambil fields dari body
        $base_url = Helper::getOSSAssetURL() . '/arsip_file/';

        // Menggunakan PDF facade Dompdf untuk load Blade view dan generate PDF
        $pdf = PDF::loadView('pdf.fab_preview', [
            'fields' => $fields,
            'base_url' => $base_url,
            'session' => $session,
        ]);

        // Kembalikan PDF sebagai binary string
        return $pdf->stream('preview_fab.pdf', ['Attachment' => false]);  // Menampilkan PDF di browser tanpa mengunduhnya
    }

    public function generateFAB(string $task_id, array $query = [])
    {
        $needCustomerSign = ($query['cust_sign'] ?? '') === 'filled';

        $baseUrl = config('app.oss_asset_url') . '/arsip_file/';

        /** =====================
         * LOAD PDF TEMPLATE
         * ===================== */
        $pdfPath = public_path('assets/pdf/FAB_NASIONAL.pdf');

        if (!file_exists($pdfPath)) {
            throw new Exception('Template PDF tidak ditemukan');
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);
        $templateId = $pdf->importPage(1);

        $pdf->AddPage();
        $pdf->useTemplate($templateId);

        /** =====================
         * GET DATA ACTIVATION
         * ===================== */
        $activation = DB::table('tis_master.customer_activation')
            ->where('Task_ID', $task_id)
            ->first();

        if (!$activation) {
            throw new Exception('Data tidak ditemukan');
        }

        /** =====================
         * SHORTEN NAMA
         * ===================== */
        $nama = explode(' ', $activation->Customer_Sub_Name);
        $shortName = '';

        foreach ($nama as $i => $n) {
            if ($i > 1) {
                $shortName .= ' ' . substr($n, 0, 1) . '.';
            } else {
                $shortName .= ' ' . $n;
            }
        }

        $activation->Customer_Sub_Name = trim($shortName);

        /** =====================
         * TANGGAL CETAK
         * ===================== */
        $now = Carbon::now('Asia/Jakarta')->format('d-m-Y');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(140, 260);
        $pdf->Write(5, $now);

        /** =====================
         * SALES SIGNATURE
         * ===================== */
        $salesSignature = DB::table('tis_main.user_l_file')
            ->select(DB::raw("CONCAT('$baseUrl', path) as url"), 'path')
            ->where('user_id', $activation->Created_By)
            ->where('category', 'SIGNATURE-AUTOGRAPH')
            ->orderByDesc('id')
            ->first();

        $pdf->SetXY(160, 270);
        $pdf->Write(5, $activation->Created_By);

        if ($salesSignature) {
            $img = Http::get($salesSignature->url)->body();
            $tmp = tempnam(sys_get_temp_dir(), 'sign');
            file_put_contents($tmp, $img);

            $pdf->Image($tmp, 160, 240, 25);
            unlink($tmp);
        }

        /** =====================
         * CUSTOMER SIGNATURE
         * ===================== */
        $customerSign = DB::table('tis_master.customer_activation_file')
            ->where('Created_By', $activation->Email_Customer)
            ->where('Kategori_File', 'SIGNATURE-AUTOGRAPH')
            ->orderByDesc('ID')
            ->first();

        $pdf->SetXY(120, 270);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Write(5, $activation->Customer_Sub_Name);

        if ($customerSign && $needCustomerSign) {
            $img = Http::get($baseUrl . $customerSign->FilePath)->body();
            $tmp = tempnam(sys_get_temp_dir(), 'custsign');
            file_put_contents($tmp, $img);

            $pdf->Image($tmp, 120, 245, 20);
            unlink($tmp);
        }

        /** =====================
         * NOMOR APLIKASI
         * ===================== */
        $region = DB::table('tis_master.region')
            ->where('Region', $activation->Region)
            ->first();

        $appCode = ($region->Kode_Region ?? '') . ' ' . $activation->Task_ID;

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetXY(140, 40);
        $pdf->Write(10, $appCode);

        /** =====================
         * CONTOH FIELD DATA
         * (fpdf tidak support form, jadi manual)
         * ===================== */
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(30, 80);
        $pdf->Write(5, $activation->Customer_Sub_Name);

        $pdf->SetXY(30, 90);
        $pdf->Write(5, $activation->Task_ID);

        $pdf->SetXY(30, 100);
        $pdf->Write(5, $activation->E_KTP);

        /** =====================
         * TOTAL PEMBAYARAN
         * ===================== */
        $vat = config('db.vat', 0.11);
        $total = $activation->Monthly_Price +
            ($activation->Monthly_Price * $vat);

        $pdf->SetXY(150, 200);
        $pdf->Write(5, number_format($total, 0, ',', '.'));

        /** =====================
         * OUTPUT PDF
         * ===================== */
        return $pdf->Output('S'); // return sebagai string
    }


     public function generateFKB(array $body, string $task_id, $custSignature = null)
    {
        // ==========================
        // VALIDASI TASK ID
        // ==========================
        $task_id = trim($task_id);

        if ($task_id === '' || $task_id === ':task_id') {
            abort(422, 'Task id tidak boleh kosong');
        }

        // ==========================
        // CUSTOMER ACTIVATION
        // ==========================
        $res = DB::select(
            'SELECT * FROM tis_master.customer_activation WHERE Task_ID = ?',
            [$task_id]
        );

        if (count($res) < 1) {
            abort(404, 'Data tidak ditemukan');
        }

        $data = $res[0];

        // ==========================
        // PROFILE SALES (EXPRESS EQUIVALENT)
        // ==========================
        $sales = Helper::getProfileSales($data->Created_By);

        // ==========================
        // ALAMAT + TANGGAL
        // ==========================
        $address_installation =
            $data->Customer_Sub_Address . ' ' .
            $data->District . ' ' .
            $data->City . ' ' .
            $data->Province . ' ' .
            $data->ZipCode;

        $createdDate = Carbon::parse($data->Created_Date)
            ->timezone('Asia/Jakarta')
            ->translatedFormat('dddd, DD MMMM YYYY');

        // ==========================
        // SIGNATURE CUSTOMER ACTIVATION
        // ==========================
        $get_signature = DB::select(
            'SELECT * FROM tis_master.customer_activation_file
             WHERE Task_ID = ? AND Kategori_File LIKE ?
             ORDER BY ID DESC',
            [$data->ID, 'SIGNATURE%']
        );

        // ==========================
        // SALES SIGNATURE PATH
        // ==========================
        $get_sign_path = DB::select(
            'SELECT path FROM tis_main.user_l_file
             WHERE user_id = ? AND category = ?',
            [
                $data->Created_By,
                $get_signature[0]->Kategori_File ?? null
            ]
        );

        $salesSignatureUrl = null;

        if (count($get_sign_path) > 0) {
            $salesSignatureUrl =
                Helper::getOSSAssetURL() . '/arsip_file/' . $get_sign_path[0]->path;
        }

        // ==========================
        // TAGIHAN
        // ==========================
        $ppn = $data->Monthly_Price * config('app.vat', 0.11);
        $total = $data->Monthly_Price + $ppn;

        // ==========================
        // DATA VIEW
        // ==========================
        $payload = [
            'data' => $data,
            'sales' => $sales['data'][0] ?? null,
            'address_installation' => $address_installation,
            'createdDate' => $createdDate,
            'ppn' => $ppn,
            'total' => $total,
            'bandwidth' => Helper::getBandwidthFromProductName([
                'product_name' => $data->Sub_Product
            ]),
            'salesSignatureUrl' => $salesSignatureUrl,
            'nowUTC' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
        ];

        // ==========================
        // GENERATE PDF
        // ==========================
        $pdf = DomPdf::loadView('pdf.fkb', $payload)
            ->setPaper('legal', 'portrait');

        return $pdf->output();
    }

    public function submitFAB(Request $request, $task_id)
    {
        $task_id = trim($task_id);
        $body    = $request->all();

        // ================= SESSION =================
        $session = Helper::getAuthorizationTokenData($request);
        $role    = $session['role'] ?? null;

        // ================= GET ACTIVATION =================
        $activation = DB::select(
            'SELECT * FROM tis_master.customer_activation WHERE Task_ID = ?',
            [$task_id]
        );

        if (count($activation) < 1) {
            throw new HttpException(404, 'Data not found.');
        }

        $activation = $activation[0];

        // ================= GENERATE FAB PDF =================
        $fabBuffer = Helper::generateFABV2([
            'params' => ['task_id' => $task_id],
            'qs'     => ['cust_sign' => 'filled']
        ]);
        $filename = Str::uuid() . '.pdf';

        $oldStatus = $activation->Status ?? null;

        if (!$oldStatus) {
            throw new HttpException(422, 'Previous status not found');
        }

        $newStatus = $body['status_to'] ?? 'FAB';
        // ================= UPDATE STATUS =================
        DB::update(
            'UPDATE tis_master.customer_activation
            SET Status = ?
            WHERE Task_ID = ?',
            [$newStatus, $task_id]
        );

        // ================= DELETE OLD META =================
        $oldMeta = DB::select(
            'SELECT ID FROM tis_master.customer_activation_file
             WHERE Task_ID = ? AND Kategori_File = "FAB"',
            [$activation->ID]
        );

        if (count($oldMeta) > 0) {
            $ids = array_map(fn ($d) => $d->ID, $oldMeta);
            DB::table('tis_master.customer_activation_file')
                ->whereIn('ID', $ids)
                ->delete();
        }

        // ================= INSERT META =================
        DB::insert(
            'INSERT INTO tis_master.customer_activation_file
            (Task_ID, Jenis_File, Kategori_File, Keterangan, FilePath, Created_By, Upload_Menu, Status_WA)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $activation->ID,
                'PDF',
                'FAB',
                null,
                $filename,
                $activation->Created_By,
                'FAB',
                1
            ]
        );
        $customerId = $activation->Customer_ID ?? null;

        if (!$customerId) {
            throw new HttpException(422, 'Customer ID not found for this task.');
        }



        // ================= INSERT NOTES =================
        DB::insert(
            'INSERT INTO tis_master.customer_activation_notes
            (Customer_ID, Task_ID, Status_From, Status, Note, Created_By, Created_Date)
            VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $customerId,
                $task_id,
                $oldStatus,       // ✅ BUKAN NULL
                $newStatus,       // ✅
                $body['note'] ?? '',
                $activation->Created_By,
                Helper::getLocalTime()
            ]
        );

        // ================= UPLOAD PDF =================
        Helper::uploadBufferToLocal([
            [
                'buffer'     => $fabBuffer,
                'target_dir' => "/var/www/html/apps/oss/arsip_file/{$filename}"
            ]
        ]);

        // ================= RESPONSE =================
        return [
            'status'  => 'success',
            'message' => $role === 'SALES'
                ? __('messages.FAB_SUCCESS.SALES')
                : __('messages.FAB_SUCCESS.CUSTOMER')
        ];
    }

    public function uploadKTP(Request $request)
    {
        // ================= VALIDASI =================
        if (!$request->hasFile('ktp')) {
            throw new HttpException(422, 'File KTP wajib dikirim');
        }

        if (!$request->input('task_id')) {
            throw new HttpException(422, 'task_id wajib dikirim');
        }

        $file   = $request->file('ktp');
        $taskId = $request->input('task_id');

        // ================= SESSION =================
        $session = Helper::getAuthorizationTokenData($request);
        $user_id = null;

        if (!empty($fields['user_id'])) {
        $user_id = is_array($fields['user_id'])
            ? $fields['user_id'][0]
            : $fields['user_id'];
        }

        if (!$user_id && $session) {
            $user_id = $session['user_id'] ?? $session['email'] ?? null;
        }

        if (!$user_id) {
            throw new HttpException(401, 'User tidak teridentifikasi');
        }


        // ================= GET ACTIVATION =================
        $activation = DB::select(
            'SELECT * FROM tis_master.customer_activation WHERE Task_ID = ?',
            [$taskId]
        );

        if (count($activation) < 1) {
            throw new HttpException(404, 'Data customer activation tidak ditemukan');
        }

        $activation = $activation[0];

        // ================= FILE INFO =================
        $extension  = '.' . $file->getClientOriginalExtension();
        $filename   = Str::uuid() . $extension;
        $fullPath   = $filename;
        $targetPath = "/var/www/html/apps/oss/arsip_file/{$fullPath}";

        $buffer = file_get_contents($file->getRealPath());

        // ================= CEK META LAMA =================
        $oldMeta = DB::select(
            'SELECT * FROM tis_master.customer_activation_file
            WHERE Task_ID = ? AND Kategori_File = "KTP"',
            [$activation->ID]
        );

        // if (count($oldMeta) > 0) {
        //     // hapus file lama
        //     Helper::deleteFileFromServer($oldMeta[0]->FilePath);

        //     DB::table('tis_master.customer_activation_file')
        //         ->where('ID', $oldMeta[0]->ID)
        //         ->delete();
        // }

        // ================= INSERT META =================
        DB::insert(
            'INSERT INTO tis_master.customer_activation_file
            (Task_ID, Jenis_File, Kategori_File, Keterangan, FilePath, Created_By, Upload_Menu, Status_WA)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $activation->ID,
                strtoupper($extension),
                null,
                null,
                $filename,
                null,
                null,
                0 // ✅ Status_WA
            ]
        );

        // ================= UPLOAD FILE =================
        Helper::uploadBufferToLocal([
            [
                'buffer'     => $buffer,
                'target_dir' => $targetPath
            ]
        ]);

        return [
            'status'  => 'success',
            'message' => 'Berhasil mengupload file KTP'
        ];
    }

 public function uploadSignature(Request $request, string $task_id): array
    {
        /** ===============================
         * 1. PREPARE DATA
         * =============================== */
        $task_id = trim($task_id);

        $b64_img        = $request->input('signature');
        $signature_type = $request->input('type');

        if (!$b64_img || !$signature_type) {
            throw new \Exception('Signature atau type tidak ditemukan');
        }

        /** ===============================
         * 2. USER IDENTIFICATION
         * =============================== */
        $tokenUser = auth()->user();

        $user_id =
            $request->input('user_id')
            ?? ($tokenUser->user_id ?? $tokenUser->email ?? 'SYSTEM');

        /** ===============================
         * 3. GET CUSTOMER INFO
         * (MENYESUAIKAN DENGAN detailCustomer DI EXPRESS)
         * =============================== */
        $get_info = DB::table('tis_master.customer_activation')
            ->where('Task_ID', $task_id)
            ->first();

        if (!$get_info) {
            throw new \Exception('Data customer tidak ditemukan');
        }

        /** ===============================
         * 4. CHECK EXISTING FILE META
         * =============================== */
        $existing = DB::table('tis_master.customer_activation_file')
            ->where('Task_ID', $get_info->ID)
            ->where('Kategori_File', 'SIGNATURE-' . $signature_type)
            ->first();

        /** ===============================
         * 5. BASE64 → FILE
         * =============================== */
        $mime = $this->checkMimeType($b64_img);
        $extension = strtoupper($mime);

        $filename = Str::uuid() . '.' . $extension;

        $binary = base64_decode($b64_img);

        if ($binary === false) {
            throw new \Exception('Base64 signature tidak valid');
        }

        /** ===============================
         * 6. DELETE OLD FILE (IF EXISTS)
         * =============================== */
        // if ($existing) {
        //     Storage::disk('sftp_oss')->delete($existing->FilePath);

        //     DB::table('tis_master.customer_activation_file')
        //         ->where('FilePath', $existing->FilePath)
        //         ->delete();
        // }

        /** ===============================
         * 7. UPLOAD TO SFTP
         * =============================== */
        // Storage::disk('sftp_oss')->put(
        //     $filename,
        //     $binary
        // );

        /** ===============================
         * 8. INSERT METADATA
         * =============================== */
        DB::table('tis_master.customer_activation_file')->insert([
            'Task_ID'       => $get_info->ID,
            'Jenis_File'    => $extension,
            'Kategori_File' => 'SIGNATURE-' . $signature_type,
            'Keterangan'    => 'tanda tangan digital untuk FAB',
            'FilePath'      => $filename,
            'Created_By'    => $user_id,
            'Upload_Menu'   => 'PROSPEK',
            'Created_Date'  => now(),
            'Status_WA'     => 0,
        ]);

        return [
            'status'  => 'success',
            'message' => 'berhasil mengupload file.',
        ];
    }

    /**
     * DETECT MIME TYPE FROM BASE64
     */
    private function checkMimeType(string $base64): string
    {
        if (str_starts_with($base64, 'data:image/')) {
            return explode('/', explode(';', $base64)[0])[1];
        }

        return 'png'; // default
    }

   public function uploadFABDocument(Request $request): array
    {
        /** ===============================
         * 1. SESSION & USER
         * =============================== */
        $session = auth()->user();

        $user_id = $session->user_id ?? $session->email ?? 'SYSTEM';

        /** ===============================
         * 2. VALIDATION (FILE EXISTENCE)
         * =============================== */
        if (
            !$request->hasFile('ktp') ||
            !$request->hasFile('signature')
        ) {
            throw new HttpException(
                400,
                'Wajib upload file terlebih dahulu (KTP, Tanda tangan)'
            );
        }

        /** ===============================
         * 3. CUSTOMER INFO
         * =============================== */
        $task_id = $request->input('task_id');

        $customer = DB::table('tis_master.customer_activation')
            ->where('Task_ID', $task_id)
            ->first();

        if (!$customer) {
            throw new HttpException(404, 'Data customer tidak ditemukan');
        }

        /** ===============================
         * 4. EXISTING META FILES
         * =============================== */
        $existingMeta = DB::table('tis_master.customer_activation_file')
            ->where('Task_ID', $customer->ID)
            ->whereIn('Kategori_File', [
                'SIGNATURE-AUTOGRAPH',
                'KTP',
            ])
            ->get();

        /** ===============================
         * 5. FILE MAP CONFIG
         * =============================== */
        $keterangan = [
            'fab' => [
                'map' => 'FAB',
                'wording' => 'File FAB',
            ],
            'ktp' => [
                'map' => 'KTP',
                'wording' => 'KTP untuk FAB',
            ],
            'signature' => [
                'map' => 'SIGNATURE-' . $request->input('signature_type'),
                'wording' => 'Tanda tangan untuk FAB',
            ],
        ];

        /** ===============================
         * 6. DELETE EXISTING FILES (ALL)
         * =============================== */
        // if ($existingMeta->count() > 0) {
        //     foreach ($existingMeta as $meta) {
        //         Storage::disk('sftp_oss')->delete($meta->FilePath);

        //         DB::table('tis_master.customer_activation_file')
        //             ->where('FilePath', $meta->FilePath)
        //             ->delete();
        //     }
        // }

        /** ===============================
         * 7. UPLOAD & INSERT NEW FILES
         * =============================== */
        foreach (['ktp', 'signature'] as $type) {
            $file = $request->file($type);

            $extension = strtoupper($file->getClientOriginalExtension());
            $newFilename = Str::uuid() . '.' . $extension;

            // upload to SFTP
            // Storage::disk('sftp_oss')->put(
            //     $newFilename,
            //     file_get_contents($file->getRealPath())
            // );

            DB::table('tis_master.customer_activation_file')->insert([
                'Task_ID'       => $customer->ID,
                'Jenis_File'    => $extension,
                'Kategori_File' => $keterangan[$type]['map'],
                'Keterangan'    => $keterangan[$type]['wording'],
                'FilePath'      => $newFilename,
                'Created_By'    => $user_id,
                'Upload_Menu'   => 'PROSPEK',
                'Status_WA'     => 1,
                'Created_Date'    => now(),
            ]);
        }

        /** ===============================
         * 8. RESPONSE
         * =============================== */
        $host = $request->getSchemeAndHttpHost();

        return [
            'status'  => 'SUCCESS',
            'message' => 'Berhasil mengupload file.',
            'data'    => [
                'fab_url' =>
                    "{$host}/api/subscription/fab/generate/{$task_id}?cust_sign=filled",
            ],
        ];
    }

   public function termsAndCondition(): Response
    {
        /** ===============================
         * 1. LOAD PASAL CONTENT
         * =============================== */
        // Pindahkan teks pasal ke file config / helper / db
        $pasal_1  = config('terms.pasal_1');
        $pasal_2  = config('terms.pasal_2');
        $pasal_3  = config('terms.pasal_3');
        $pasal_4  = config('terms.pasal_4');
        $pasal_5  = config('terms.pasal_5');
        $pasal_6  = config('terms.pasal_6');
        $pasal_7  = config('terms.pasal_7');
        $pasal_8  = config('terms.pasal_8');
        $pasal_9  = config('terms.pasal_9');
        $pasal_10 = config('terms.pasal_10');
        $pasal_11 = config('terms.pasal_11');
        $pasal_12 = config('terms.pasal_12');
        $pasal_13 = config('terms.pasal_13');
        $pasal_14 = config('terms.pasal_14');
        $pasal_15 = config('terms.pasal_15');

        /** ===============================
         * 2. GENERATE PDF
         * =============================== */
        $pdf = Pdf::loadView('pdf.terms-and-condition', compact(
            'pasal_1',
            'pasal_2',
            'pasal_3',
            'pasal_4',
            'pasal_5',
            'pasal_6',
            'pasal_7',
            'pasal_8',
            'pasal_9',
            'pasal_10',
            'pasal_11',
            'pasal_12',
            'pasal_13',
            'pasal_14',
            'pasal_15'
        ))
            ->setPaper('legal')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont'     => 'Arial',
            ]);

        /** ===============================
         * 3. STREAM PDF (SEPERTI EXPRESS)
         * =============================== */
        return $pdf->stream('terms-and-condition.pdf');
    }
}
