# 1.3.3
* Fixed getting line items of an ESD order
* Improved the language of `purchase date` column within account > downloads page

# 1.3.2
* Fixed to add return response for the download handling

# 1.3.1
* Fixed the issue can't finish order when the cart has a physical article and digital article

# 1.3.0
* ESD supported for the ESD video
* You're able to switch from ESD video to ESD normal
* Improved the template of showing ESD data in the account download page

# 1.2.14
* Improved download handling

# 1.2.13
* Removed `updateTo120` method of the update version, because it has been replaced with the migration 

# 1.2.12
* Fixed the error of using wrong class names and change the namespace and directory of events

# 1.2.11
* Fixed that the navigation isn't shown on the download account page
* Improved to send the ESD email by the business events
* Added the `Resend email download` and `Resend email serial` buttons to resend ESD email in the order detail pages
* we fixed an issue for corrupted .zip files on Windows

# 1.2.10
* Fixed the url in the mail template, change from url() to rawUrl() to get the sales channel domain
* Improved send mail feature, you can send the esd email to the buyer buy change the payment status to paid with the `Send email to customer` toggle is enable

# 1.2.9
* Fixed reload the remaining download data on Shopware >= v6.3.2.0, can update the remaining download after click download now

# 1.2.8
* Made a hotfix to send the download email on Shopware version 6.3.3.0

# 1.2.7
* fixed a bug with the general terms and condition checkbox during the checkout

# 1.2.6
* Updated to disable reload the page to get the remaining download data on Shopware version 6.3.2.0 >= 6.3.3.0

# 1.2.5
* show always revocation for digital downloads. Text Snippet Key is `sasEsd.checkout.confirmESD`

# 1.2.4
* fixed umlauts while downloading the .zip within the storefront

# 1.2.3
* added various filetypes to be able to upload also documents
* it's also possible to upload your very own .zip file

# 1.2.2
* fixed issue with terms of use

# 1.2.1
* added instant download badge on product detail
* added ESD withdrawal notice within checkout

# 1.2.0
* Added Download confirmation template
* Added Serial confirmation template
* Added Download Limitation of ESD
* Improved API documentation

# 1.1.0

* Added multiple file uploads
* Added order number to downloads table
* Fixed a bug within the Administration that an ESD does not load,
if you refresh the whole site directly

# 1.0.0

* First release in Store
