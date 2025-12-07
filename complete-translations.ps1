# Complete Arabic translations for all pages

# REQUESTS PAGE
$file = 'resources\views\requests\index.blade.php'
$content = Get-Content $file -Raw

# Title and headings
$content = $content -replace "@section\('title', 'Requests'\)", "@section('title', __('messages.requests'))"
$content = $content -replace '<h1[^>]*>Requests</h1>', '<h1 class="text-3xl font-bold text-gray-800">{{ __(''messages.requests'') }}</h1>'

# Buttons
$content = $content -replace '>Create Request</', '>{{ __(''messages.create_request'') }}</'
$content = $content -replace '>Apply Filters</', '>{{ __(''messages.filter'') }}</'
$content = $content -replace 'Add New Request', "{{ __('messages.create_request') }}"

# Table headers
$content = $content -replace '>ID</', '>ID</'
$content = $content -replace '>Item</', '>{{ __(''messages.items'') }}</'
$content = $content -replace '>Quantity</', '>{{ __(''messages.quantity'') }}</'
$content = $content -replace '>Description</', '>{{ __(''messages.description'') }}</'

# Form labels
$content = $content -replace '>Select Item</', '>{{ __(''messages.items'') }}</'
$content = $content -replace 'placeholder="Search items', "placeholder=`"{{ __('messages.search') }}"
$content = $content -replace 'placeholder="Enter quantity', "placeholder=`"{{ __('messages.quantity') }}"

# Notification messages
$content = $content -replace '''Request created successfully!''', '''{{ __(''''messages.request_created_success'''') }}'''
$content = $content -replace '''Request approved!''', '''{{ __(''''messages.request_approved'''') }}'''
$content = $content -replace '''Request rejected!''', '''{{ __(''''messages.request_rejected'''') }}'''
$content = $content -replace '''Request fulfilled successfully!''', '''{{ __(''''messages.request_fulfilled'''') }}'''

Set-Content -Path $file -Value $content -NoNewline
Write-Host "Updated $file"

# PURCHASE ORDERS PAGE
$file = 'resources\views\purchase-orders\index.blade.php'
$content = Get-Content $file -Raw

$content = $content -replace "@section\('title', 'Purchase Orders'\)", "@section('title', __('messages.purchase_orders'))"
$content = $content -replace '<h1[^>]*>Purchase Orders</h1>', '<h1 class="text-3xl font-bold text-gray-800">{{ __(''messages.purchase_orders'') }}</h1>'
$content = $content -replace '>Create Purchase Order</', '>{{ __(''messages.create_purchase_order'') }}</'
$content = $content -replace '>Apply Filters</', '>{{ __(''messages.filter'') }}</'
$content = $content -replace 'Add New Purchase Order', "{{ __('messages.create_purchase_order') }}"
$content = $content -replace '>Items</', '>{{ __(''messages.items'') }}</'

# Notification messages
$content = $content -replace '''Purchase order created successfully!''', '''{{ __(''''messages.po_created_success'''') }}'''
$content = $content -replace '''Purchase order updated successfully!''', '''{{ __(''''messages.po_updated_success'''') }}'''
$content = $content -replace '''Purchase order approved!''', '''{{ __(''''messages.po_approved'''') }}'''
$content = $content -replace '''Purchase order rejected!''', '''{{ __(''''messages.po_rejected'''') }}'''

Set-Content -Path $file -Value $content -NoNewline
Write-Host "Updated $file"

# INVOICES PAGE
$file = 'resources\views\invoices\index.blade.php'
$content = Get-Content $file -Raw

$content = $content -replace "@section\('title', 'Invoices'\)", "@section('title', __('messages.invoices'))"
$content = $content -replace '<h1[^>]*>Invoices</h1>', '<h1 class="text-3xl font-bold text-gray-800">{{ __(''messages.invoices'') }}</h1>'
$content = $content -replace '>Create Invoice</', '>{{ __(''messages.create_invoice'') }}</'
$content = $content -replace '>Apply Filters</', '>{{ __(''messages.filter'') }}</'
$content = $content -replace 'Add New Invoice', "{{ __('messages.create_invoice') }}"
$content = $content -replace '>Items</', '>{{ __(''messages.items'') }}</'

# Notification messages
$content = $content -replace '''Invoice created successfully! Items have been added to inventory.''', '''{{ __(''''messages.invoice_created_success'''') }}'''
$content = $content -replace '''Invoice updated successfully!''', '''{{ __(''''messages.invoice_updated_success'''') }}'''

Set-Content -Path $file -Value $content -NoNewline
Write-Host "Updated $file"

Write-Host "`nAll translations completed successfully!"
