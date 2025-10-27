# SadrCod Connector for PrestaShop

**Version:** 1.0.0
**Author:** Mohammad Babaei - [AdsChi.com](https://adschi.com)
**Compatibility:** PrestaShop 1.7.x

## Overview

This module seamlessly integrates your PrestaShop store with the **SadrCod** postal web service. It automates the process of registering orders with the shipping provider, retrieving tracking numbers, and saving them to the corresponding customer orders.

### Key Features

-   **Automatic Order Submission**: Automatically sends order details to the SadrCod API as soon as an order's status is updated to a configurable state (e.g., "Processing in progress").
-   **Full Multi-Store Support**: Each store can have its own unique API settings and submission rules, making it fully compatible with a multi-store environment.
-   **Tracking Number Integration**: Fetches the tracking number upon successful submission and saves it to the order's shipping details, making it visible to the customer.
-   **Dynamic Destination City Codes**: Intelligently maps the order's destination state/province to the required API city code, using a configurable default for unmatched locations.
-   **Advanced Logging**: Logs every API request (successful or failed) in a dedicated page in the PrestaShop back office for easy monitoring and debugging.
-   **Print Label Ready**: If the API response includes a URL for a shipping label, a "Print" button is made available in the log report.
-   **SMS Integration Hook**: Provides a standard PrestaShop hook (`actionSadrCodSendSms`) to allow any SMS module to send the tracking number to the customer.

---

## Installation and Configuration

### 1. Installation

1.  Download the latest version of the module from the repository.
2.  Upload the `sadrcodconnector` folder to your PrestaShop `modules/` directory.
3.  Log in to your PrestaShop back office and navigate to **Modules > Module Manager**.
4.  Find the **SadrCod Connector** module in the list and click **Install**.

### 2. Configuration

After installation, click **Configure** to access the module's settings page.

-   **Username**: Enter your SadrCod API username.
-   **Password**: Enter your SadrCod API password. (For security, this field will be blank after saving).
-   **Target Order States**: Select one or more order statuses. When an order is updated to any of these statuses, it will be sent to SadrCod.
-   **Default Packaging Weight (grams)**: Enter the weight of your standard packaging material (e.g., box, envelope) in grams. This will be added to the total weight of the products.
-   **Default Destination City Code**: Enter a default city code from the SadrCod API list (e.g., `1` for Tehran). This code will be used if the module cannot determine the customer's state.

**Important Note for Multi-Store Users**: If you are running multiple shops, use the store selector at the top of the page to configure the module for each shop individually.

---

## Usage

### Log Report

To view the status of submitted orders, go to **Orders > SadrCod Logs** in your back office menu. This page displays a complete list of submissions, their status (success/error), tracking number, and the date of the attempt.

### SMS Integration (For Developers)

This module does not send SMS messages directly. Instead, it provides a custom hook named `actionSadrCodSendSms` for maximum flexibility. You can integrate your existing SMS module by making it listen to this hook.

**Hook Parameters:**

-   `mobile_phone` (string): The customer's mobile phone number.
-   `tracking_number` (string): The shipping tracking number.
-   `order_reference` (string): The order reference code.
-   `customer_name` (string): The customer's full name.

**Example Usage in an SMS Module:**

```php
public function hookActionSadrCodSendSms($params)
{
    $mobile = $params['mobile_phone'];
    $tracking = $params['tracking_number'];
    $reference = $params['order_reference'];
    $name = $params['customer_name'];

    $message = "Dear {$name}, your order {$reference} has been shipped. Tracking number: {$tracking}";

    // Your SMS sending logic here...
    // Example: YourSmsClass::send($mobile, $message);
}
```

---
<br>

# ماژول اتصال پرستاشاپ به صدرکد (SadrCod Connector)

**نسخه:** 1.0.0
**توسعه‌دهنده:** محمد بابایی - [AdsChi.com](https://adschi.com)
**سازگار با پرستاشاپ:** 1.7.x

## معرفی

این ماژول فروشگاه پرستاشاپ شما را به وب‌سرویس پستی **صدرکد** متصل می‌کند. با استفاده از این ماژول، فرآیند ثبت سفارشات در سیستم پستی به صورت کاملاً خودکار انجام شده و کد رهگیری پستی به صورت سیستمی برای مشتریان ثبت و ارسال می‌گردد.

### قابلیت‌های کلیدی

-   **ارسال خودکار سفارشات**: به محض تغییر وضعیت یک سفارش به حالت دلخواه شما (مثلاً "آماده‌سازی در انبار")، اطلاعات آن به صورت خودکار برای صدرکد ارسال می‌شود.
-   **پشتیبانی کامل از چندفروشگاهی (Multi-Store)**: هر فروشگاه می‌تواند تنظیمات API و قوانین ارسال منحصر به فرد خود را داشته باشد.
-   **دریافت و ثبت کد رهگیری**: پس از ثبت موفق سفارش، کد رهگیری از صدرکد دریافت و در بخش "حمل و نقل" سفارش در پرستاشاپ ثبت می‌شود.
-   **تعیین هوشمند کد شهر مقصد**: ماژول به صورت خودکار استان مقصد سفارش را به کد شهر مورد نیاز API نگاشت می‌کند و از یک کد پیش‌فرض برای موارد خاص استفاده می‌کند.
-   **صفحه گزارشات پیشرفته**: تمام درخواست‌های ارسالی به API (موفق و ناموفق) در یک صفحه جداگانه در پنل مدیریت ثبت می‌شوند تا به راحتی قابل پیگیری باشند.
-   **آماده برای چاپ برچسب**: در صورت ارائه لینک برچسب توسط API، دکمه چاپ آن در صفحه گزارشات نمایش داده می‌شود.
-   **قابلیت اتصال به سیستم پیامک**: ماژول دارای یک هوک (Hook) استاندارد برای اتصال به هر سیستم پیامکی است تا کد رهگیری را برای مشتریان SMS کند.

---

## راهنمای نصب و راه‌اندازی

### ۱. نصب ماژول

1.  آخرین نسخه ماژول را از مخزن (Repository) دانلود کنید.
2.  پوشه `sadrcodconnector` را در مسیر `modules/` هاست خود آپلود کنید.
3.  وارد پنل مدیریت پرستاشاپ خود شوید و به بخش **ماژول‌ها > مدیر ماژول** بروید.
4.  در لیست ماژول‌ها، **SadrCod Connector** را پیدا کرده و روی دکمه **Install** کلیک کنید.

### ۲. تنظیمات ماژول

پس از نصب، روی دکمه **Configure** کلیک کنید تا وارد صفحه تنظیمات ماژول شوید.

-   **نام کاربری (Username)**: نام کاربری خود برای ورود به API صدرکد را وارد کنید.
-   **رمز عبور (Password)**: رمز عبور API خود را وارد کنید. (برای امنیت، این فیلد پس از ذخیره خالی نمایش داده می‌شود).
-   **وضعیت‌های سفارش هدف (Target Order States)**: یک یا چند وضعیت سفارش را انتخاب کنید. هرگاه سفارشی به یکی از این وضعیت‌ها تغییر کند، به صدرکد ارسال خواهد شد.
-   **وزن پیش‌فرض بسته‌بندی (گرم)**: وزن جعبه یا پاکت بسته‌بندی را به گرم وارد کنید. این وزن به مجموع وزن محصولات اضافه می‌شود.
-   **کد شهر مقصد پیش‌فرض (Default Destination City Code)**: یک کد شهر از لیست کدهای صدرکد (مثلاً `1` برای تهران) وارد کنید. اگر ماژول نتواند استان مشتری را تشخیص دهد، از این کد استفاده خواهد کرد.

**نکته مهم در حالت چندفروشگاهی**: اگر از چند فروشگاه به صورت همزمان استفاده می‌کنید، حتماً از منوی بالای صفحه، فروشگاه مورد نظر خود را انتخاب کرده و تنظیمات را برای هر فروشگاه به صورت جداگانه ذخیره کنید.

---

## استفاده از ماژول

### صفحه گزارشات

برای مشاهده وضعیت سفارشات ارسال شده، از منوی پنل مدیریت به بخش **سفارش‌ها > SadrCod Logs** بروید. در این صفحه می‌توانید لیست کامل سفارشات، وضعیت ارسال (موفق/ناموفق)، کد رهگیری و تاریخ ارسال را مشاهده کنید.

### اتصال به ماژول پیامک (برای توسعه‌دهندگان)

این ماژول به صورت مستقیم پیامک ارسال نمی‌کند، اما یک هوک سفارشی به نام `actionSadrCodSendSms` را فراهم می‌کند. شما می‌توانید ماژول پیامک خود را طوری تغییر دهید که به این هوک گوش داده و پیامک را ارسال کند.

**پارامترهای ارسالی توسط هوک:**

-   `mobile_phone` (string): شماره موبایل مشتری
-   `tracking_number` (string): کد رهگیری پستی
-   `order_reference` (string): شماره مرجع سفارش
-   `customer_name` (string): نام کامل مشتری

**نمونه کد برای استفاده در ماژول پیامک:**

```php
public function hookActionSadrCodSendSms($params)
{
    $mobile = $params['mobile_phone'];
    $tracking = $params['tracking_number'];
    $reference = $params['order_reference'];
    $name = $params['customer_name'];

    $message = "مشتری عزیز {$name}، سفارش شما با شماره {$reference} ارسال شد. کد رهگیری: {$tracking}";

    // Your SMS sending logic here...
    // Example: YourSmsClass::send($mobile, $message);
}
```
