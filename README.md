# ESD / Product downloads for Shopware 6
You can finally offer digital downloads to your customers.
After purchasing an ESD / download product, the customer will be able to download the product within 
his account within a new download section.

## Installation
Go to your project root folder and execute the following commands:

`cd custom/plugins`

`git clone git@github.com:Shape-and-Shift/shopware-esd.git SasEsd`

`bin/console plugin:refresh && bin/console plugin:install SasEsd --activate`

The plugin is now installed and activated.

## Storefront customer Account
Within the storefront the customer will have a new menu entry called **Downloads** 
where all ESD products will be listed.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592203675/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.46.12_a6wpol.png)

Only ESD products where the payment status is set to **paid** will be listed here **or** 
if the ESD product is for free e.g €0,00

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592203665/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.47.32_luigg7.png)
## Tax rates
You might want to have different tax rates depending on the country where the buyer comes from.

To archive this, the ESD plugin will create a new tax group while installing the plugin.
Within the administration go to `settings->tax` and you'll find the newly created tax group "ESD tax rates".
This group contains all European countries with it's VAT rates.

The default tax rate is set to 19%. So if you're not logged in for example and therefore 
don't have a shipping country yet, you will see the default tax rate.

**Please double check the VAT rates, to be 100% sure those are correct.**

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592202722/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.29.27_ndmjik.png)

You need to assign this **ESD tax group** to your article.
Go to your ESD product and select the **ESD** tax group within the price section.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592202723/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.30.59_iumrap.png)

Now: If your shop is based for example in Germany ( 19% VAT ) and a customer from Portugal ( 23% VAT ) is ordering an ESD article
within your shop - The ESD product will be taxed with the 23% VAT from Portugal, and not with 19% from Germany.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592203215/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.38.59_uagkc1.png)
