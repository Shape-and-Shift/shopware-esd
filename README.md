# ESD / Product downloads for Shopware 6

## Installation
You have bs ways to install the 

## Tax rates
You might want to have different tax rates depending on the country where the buyer comes from.

To archive this, the ESD plugin will create a new tax group while installing the plugin.
Within the administration go to settings->tax and you'll find the newly created tax group "ESD tax rates".
This group contains all European countries with it's VAT rates.

**Please double check the VAT rates, to be 100% sure those are correct.**

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592202722/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.29.27_ndmjik.png)

You need to assign this **ESD tax group** to your article.
Go to your ESD product and select the **ESD** tax group within the price section.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592202723/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.30.59_iumrap.png)

Now: If your shop is based for example in Germany ( 19% VAT ) and a customer from Portugal ( 23% VAT ) is ordering an ESD article
within your shop - The ESD product will be taxed with the 23% VAT from Portugal, and not with 19% from Germany.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592203215/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.38.59_uagkc1.png)
