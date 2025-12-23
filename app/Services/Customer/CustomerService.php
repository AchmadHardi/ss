<?php


namespace App\Services\Customer;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\Helper;

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

        DB::connection('tis_master')->table('idmall__customer_activation')->insert([
            'Customer_ID' => $body['provider_id'] ?? env('RETAIL_CUSTOMER_ID', 1),
            'Task_ID' => $taskId,
            'Group_Task_ID' => $taskId,
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
        ]);

        return [
            'status' => 'success',
            'message' => 'berhasil membuat pengajuan form entri prospek'
        ];
    }




    public function getLeadCustomer($body)
    {
        // Logic mengambil lead customer
    }

    public function pushLeadCustomer($body)
    {
        // Logic push lead customer
    }

    public function previewFAB($body)
    {
        // Logic generate PDF preview
    }

    public function generateFAB($task_id)
    {
        // Logic generate FAB by task_id
    }

    public function generateFKB($body, $task_id)
    {
        // Logic generate FKB
    }

    public function submitFAB($body, $task_id)
    {
        // Logic submit FAB
    }

    public function uploadKTP($fields, $files)
    {
        // Logic upload KTP
    }

    public function uploadSignature($fields, $files, $task_id)
    {
        // Logic upload signature
    }

    public function uploadFABDocument($fields, $files)
    {
        // Logic upload document
    }

    public function termsAndCondition()
    {
        // Logic terms and condition
    }
}
