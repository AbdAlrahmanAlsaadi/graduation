@php
    $cairoFont = 'file:///' . str_replace('\\', '/', public_path('fonts/Cairo-Regular.ttf'));
    $cairoFont = str_replace(' ', '%20', $cairoFont);

    $status = $contract->status ?? 'Draft';

    $statusClass = match ($status) {
        'Active' => 'status-active',
        'Completed' => 'status-completed',
        'Cancelled' => 'status-cancelled',
        default => 'status-draft',
    };

    $currency = $contract->currency ?? 'USD';

    $currencyLabel = match ($currency) {
        'SYP' => 'ليرة سورية',
        'USD' => 'دولار أمريكي',
        default => $currency,
    };

    $companySignature = null;
    if (!empty($contract->company_signature)) {
        $companySignature = 'file:///' . str_replace('\\', '/', public_path($contract->company_signature));
        $companySignature = str_replace(' ', '%20', $companySignature);
    }

    $ownerSignature = null;
    if (!empty($contract->owner_signature)) {
        $ownerSignature = 'file:///' . str_replace('\\', '/', public_path($contract->owner_signature));
        $ownerSignature = str_replace(' ', '%20', $ownerSignature);
    }
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عقد بين الشركة والمالك</title>

    <style>
        @font-face {
            font-family: 'Cairo';
            src: url('{{ $cairoFont }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Cairo', Arial, sans-serif;
            direction: rtl;
            unicode-bidi: embed;
            background: #ffffff;
            color: #1f2937;
            font-size: 13px;
            line-height: 1.7;
        }

        .page {
            width: 100%;
            min-height: 100%;
            background: #ffffff;
            padding: 28px 34px 0;
        }

        .header {
            position: relative;
            height: 150px;
            background: #0b2f52;
            overflow: hidden;
            color: #ffffff;
            margin-bottom: 22px;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 38%;
            height: 150px;
            background: #103f6d;
        }

        .header::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 18px;
            height: 150px;
            background: #d6a331;
        }

        .header-table {
            position: relative;
            z-index: 2;
            width: 100%;
            border-collapse: collapse;
            height: 150px;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
            padding: 0 28px;
        }

        .header-title {
            width: 45%;
            text-align: right;
        }

        .header-title h1 {
            margin: 0;
            font-size: 25px;
            font-weight: bold;
            color: #ffffff;
        }

        .header-title .en {
            margin-top: 12px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .5px;
            color: #e5edf5;
            direction: ltr;
            text-align: right;
        }

        .contract-no-badge {
            display: inline-block;
            margin-top: 12px;
            background: #d6a331;
            color: #ffffff;
            padding: 5px 14px;
            border-radius: 18px;
            font-size: 11px;
            font-weight: bold;
        }

        .company-head {
            width: 55%;
            text-align: left;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .company-en {
            font-size: 12px;
            color: #e6edf5;
            direction: ltr;
        }

        .department {
            font-size: 11px;
            color: #e6edf5;
            direction: ltr;
            margin-top: 4px;
        }

        .intro-box {
            border: 1px solid #e1e7ef;
            border-right: 5px solid #d6a331;
            background: #f8fafc;
            padding: 13px 16px;
            margin-bottom: 22px;
            color: #475569;
            text-align: center;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #0b2f52;
            margin: 20px 0 10px;
            padding-bottom: 7px;
            border-bottom: 2px solid #d6a331;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .data-table td {
            border: 1px solid #dce3eb;
            padding: 9px 12px;
            vertical-align: middle;
        }

        .data-table .label {
            width: 28%;
            background: #f1f5f9;
            color: #0b2f52;
            font-weight: bold;
            white-space: nowrap;
        }

        .data-table .value {
            background: #ffffff;
            color: #334155;
            font-weight: normal;
        }

        .contract-main-table .value {
            text-align: center;
        }

        .amount {
            font-size: 15px;
            font-weight: bold;
            color: #0b2f52;
        }

        .status {
            display: inline-block;
            min-width: 86px;
            text-align: center;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-draft {
            background: #eeeeee;
            color: #555555;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .cards-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .cards-table > tbody > tr > td {
            width: 50%;
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .card-right {
            padding-left: 8px !important;
        }

        .card-left {
            padding-right: 8px !important;
        }

        .card {
            border: 1px solid #dce3eb;
            background: #ffffff;
            margin-bottom: 14px;
        }

        .card-header {
            background: #0b2f52;
            color: #ffffff;
            padding: 8px 13px;
            font-size: 14px;
            font-weight: bold;
        }

        .card-body {
            padding: 12px 14px;
            min-height: 112px;
        }

        .mini-row {
            margin-bottom: 7px;
        }

        .mini-label {
            display: inline-block;
            color: #0b2f52;
            font-weight: bold;
            min-width: 95px;
        }

        .project-strip {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 18px;
        }

        .project-strip td {
            border: 1px solid #dce3eb;
            padding: 10px 12px;
        }

        .project-strip .label {
            width: 22%;
            background: #f1f5f9;
            color: #0b2f52;
            font-weight: bold;
        }

        .project-strip .value {
            text-align: center;
            font-weight: bold;
            color: #334155;
        }

        .terms-box {
            border: 1px solid #dce3eb;
            background: #f8fafc;
            padding: 14px 18px;
            margin-top: 8px;
        }

        .terms-box ol {
            margin: 0;
            padding-right: 22px;
        }

        .terms-box li {
            margin-bottom: 5px;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 36px;
            margin-bottom: 26px;
        }

        .signatures td {
            width: 50%;
            border: none;
            text-align: center;
            vertical-align: top;
            padding: 0 18px;
        }

        .signature-box {
            border: 1px solid #dce3eb;
            background: #ffffff;
            padding: 16px;
            min-height: 150px;
        }

        .signature-title {
            color: #0b2f52;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .signature-img-wrap {
            height: 68px;
            margin-bottom: 10px;
            text-align: center;
        }

        .signature-img {
            max-height: 65px;
            max-width: 190px;
            object-fit: contain;
        }

        .signature-line {
            border-top: 1px solid #64748b;
            padding-top: 8px;
            color: #475569;
            font-size: 12px;
        }

        .footer {
            margin: 0 -34px;
            background: #0b2f52;
            color: #ffffff;
            padding: 12px 30px;
            font-size: 11px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            border: none;
            color: #ffffff;
            vertical-align: middle;
        }

        .footer-left {
            text-align: left;
            direction: ltr;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }

        .gold {
            color: #d6a331;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="page">

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-title">
                    <h1>عقد بين شركة ومالك</h1>
                    <div class="en">CONTRACT BETWEEN COMPANY & OWNER</div>
                    <div class="contract-no-badge">
                        رقم العقد: {{ $contract->contract_no }}
                    </div>
                </td>

                <td class="company-head">
                    <div class="company-name">شركة الإنشاءات المتقدمة</div>
                    <div class="company-en">Advanced Construction Co.</div>
                    <div class="department">Contract Management Department</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="intro-box">
        تم الاتفاق بين الطرفين المذكورين أدناه على البنود والشروط الواردة في هذا العقد،
        وذلك بخصوص المشروع المحدد ضمن بيانات العقد والمشروع.
    </div>

    <div class="section-title">1. بيانات العقد</div>

    <table class="data-table contract-main-table">
        <tr>
            <td class="label">رقم العقد</td>
            <td class="value">{{ $contract->contract_no }}</td>
        </tr>

        <tr>
            <td class="label">عنوان العقد</td>
            <td class="value">{{ $contract->title }}</td>
        </tr>

        <tr>
            <td class="label">تاريخ العقد</td>
            <td class="value">{{ $contract->contract_date }}</td>
        </tr>

        <tr>
            <td class="label">تاريخ بدء التنفيذ</td>
            <td class="value">{{ $contract->start_date }}</td>
        </tr>

        <tr>
            <td class="label">تاريخ انتهاء التنفيذ</td>
            <td class="value">{{ $contract->end_date ?? '—' }}</td>
        </tr>

        <tr>
            <td class="label">قيمة العقد</td>
            <td class="value amount">
                {{ number_format($contract->contract_value, 2) }} {{ $currencyLabel }}
            </td>
        </tr>

        <tr>
            <td class="label">العملة</td>
            <td class="value">{{ $currency }} - {{ $currencyLabel }}</td>
        </tr>

        <tr>
            <td class="label">حالة العقد</td>
            <td class="value">
                <span class="status {{ $statusClass }}">
                    {{ $status }}
                </span>
            </td>
        </tr>

        <tr>
            <td class="label">وصف العقد</td>
            <td class="value">{{ $contract->description }}</td>
        </tr>
    </table>

    <table class="cards-table">
        <tr>
            <td class="card-right">
                <div class="card">
                    <div class="card-header">2. بيانات الشركة</div>
                    <div class="card-body">
                        <div class="mini-row">
                            <span class="mini-label">اسم الشركة:</span>
                            شركة الإنشاءات المتقدمة
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">القسم:</span>
                            إدارة العقود
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">الصفة:</span>
                            الطرف المنفذ
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">3. بيانات المالك</div>
                    <div class="card-body">
                        <div class="mini-row">
                            <span class="mini-label">اسم المالك:</span>
                            {{ $contract->owner->name ?? '—' }}
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">البريد:</span>
                            {{ $contract->owner->email ?? '—' }}
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">المعرف:</span>
                            {{ $contract->owner->internal_id ?? '—' }}
                        </div>
                    </div>
                </div>
            </td>

            <td class="card-left">
                <div class="card">
                    <div class="card-header">4. بيانات المشروع</div>
                    <div class="card-body">
                        <div class="mini-row">
                            <span class="mini-label">اسم المشروع:</span>
                            {{ $contract->project->name ?? '—' }}
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">موقع المشروع:</span>
                            {{ $contract->project->location ?? '—' }}
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">حالة المشروع:</span>
                            {{ $contract->project->status ?? '—' }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">5. ملخص مالي وزمني</div>
                    <div class="card-body">
                        <div class="mini-row">
                            <span class="mini-label">القيمة:</span>
                            {{ number_format($contract->contract_value, 2) }} {{ $currencyLabel }}
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">البداية:</span>
                            {{ $contract->start_date }}
                        </div>
                        <div class="mini-row">
                            <span class="mini-label">النهاية:</span>
                            {{ $contract->end_date ?? 'غير محدد' }}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">6. مشروع العقد</div>

    <table class="project-strip">
        <tr>
            <td class="label">اسم المشروع</td>
            <td class="value">{{ $contract->project->name ?? '—' }}</td>

            <td class="label">رقم المشروع</td>
            <td class="value">{{ $contract->project->id ?? $contract->project_id }}</td>
        </tr>
    </table>

    <div class="section-title">7. الشروط العامة</div>

    <div class="terms-box">
        <ol>
            <li>يلتزم الطرفان بجميع البنود والشروط الواردة في هذا العقد.</li>
            <li>تلتزم الشركة بتنفيذ الأعمال وفقاً للمواصفات والمدة الزمنية المتفق عليها.</li>
            <li>يلتزم المالك بسداد قيمة العقد حسب ما يتم الاتفاق عليه بين الطرفين.</li>
            <li>لا يجوز تعديل أو إلغاء أي بند من بنود هذا العقد إلا بموجب اتفاق مكتوب بين الطرفين.</li>
            <li>يخضع هذا العقد للأنظمة واللوائح المعمول بها.</li>
        </ol>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="signature-box">
                    <div class="signature-title">توقيع الشركة</div>

                    <div class="signature-img-wrap">
                        @if($companySignature)
                            <img src="{{ $companySignature }}" class="signature-img" alt="Company Signature">
                        @endif
                    </div>

                    <div class="signature-line">
                        الاسم: شركة الإنشاءات المتقدمة<br>
                        التاريخ: {{ $contract->contract_date }}
                    </div>
                </div>
            </td>

            <td>
                <div class="signature-box">
                    <div class="signature-title">توقيع المالك</div>

                    <div class="signature-img-wrap">
                        @if($ownerSignature)
                            <img src="{{ $ownerSignature }}" class="signature-img" alt="Owner Signature">
                        @endif
                    </div>

                    <div class="signature-line">
                        الاسم: {{ $contract->owner->name ?? '—' }}<br>
                        التاريخ: {{ $contract->contract_date }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-right">
                    <span class="gold">©</span> شركة الإنشاءات المتقدمة
                </td>
                <td class="footer-center">
                    Contract No: {{ $contract->contract_no }}
                </td>
                <td class="footer-left">
                    Generated Contract PDF
                </td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
