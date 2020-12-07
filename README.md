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

## Setup of an ESD product
Each product will have a new tab called **ESD** where you can upload a file and/or
assign serial numbers.

A product will automaticlly be an ESD product if one of those cases are true.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592204095/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.52.32_suscrx.png)

All files which will be uploaded here are **private** which means they're not visible within the Media Manager,
or available to the public. 

### Serial numbers
If you check the toggle **Enable serial numbers** a new card will be visible where you can create or import your serial numbers.
![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592204308/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.57.55_kfnj5j.png)

#### Manually import serial numbers

If you want to manually add serial numbers a modal will be opened where you can type in your serial numbers manually.
Each new line is a new serial number.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592204554/ESD%20Docs/Bildschirmfoto_2020-06-15_um_10.02.27_jyjo00.png)

After clicking the button **import serial numbers** those will be directly listed within the serial number table.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592204594/ESD%20Docs/Bildschirmfoto_2020-06-15_um_10.03.05_j59jam.png)

If a customer now buys the ESD article the next free serial number will be assigned to the customer.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592204693/ESD%20Docs/Bildschirmfoto_2020-06-15_um_10.04.31_wpknkh.png)

## Storefront customer Account
Within the storefront the customer will have a new menu entry called **Downloads** 
where all ESD products will be listed.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592203675/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.46.12_a6wpol.png)

To manually create or import serial numbers from a CSV file, just click on of the buttons which fits your needs.
![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592204434/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.59.59_uii9qw.png)

Only ESD products where the payment status is set to **paid** will be listed here **or** 
if the ESD product is for free e.g €0,00

If the ESD product contains a serial number, it will also be listed.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592203665/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.47.32_luigg7.png)
## Tax rates
You might want to have different tax rates depending on the country where the buyer comes from.

Within the administration go to `settings->tax` and add your new tax-group, for example "ESD tax rates".
Now you add the different countries with it's tax rates. For example you can add Germany with a 19% tax rate.

You also have to choose a default tax rate. So if you're not logged in for example and therefore 
don't have a shipping country yet, you will see the default tax rate.

**Please double check the VAT rates, to be 100% sure those are correct.**

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592202722/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.29.27_ndmjik.png)

You need to assign this **ESD tax group** to your article.
Go to your ESD product and select the **ESD** tax group within the price section.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592202723/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.30.59_iumrap.png)

Now: If your shop is based for example in Germany ( 19% VAT ) and a customer from Portugal ( 23% VAT ) is ordering an ESD article
within your shop - The ESD product will be taxed with the 23% VAT from Portugal, and not with 19% from Germany.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1592203215/ESD%20Docs/Bildschirmfoto_2020-06-15_um_09.38.59_uagkc1.png)


## Add serial numbers via API endpoint
Thanks to the new concept of Shopware 6 it's easy peasy to create new serial numbers with the API.
You just have to make a **POST** request to the API endpoint `/api/v2/sas-product-esd-serial`.
Within the `body` you need three parameters.

* `esdId` which is the ID of the ESD article
* `serial` which represents the actual serial number
* `id` which has to be a random generated UUID

Full **POST** example to the endpoint `/api/v2/sas-product-esd-serial`:
```
{
	"esdId":"e60281b55f584ccf87d722f51af8499b",
	"serial":"testAPI",
	"id":"a695aac053234fd3a6ab79e57a4229fb"
}
```

<details>
 <summary>So a fully working request would look like this example with Postman</summary>
 <img src="https://res.cloudinary.com/dtgdh7noz/image/upload/v1593785529/Bildschirmfoto_2020-07-03_um_16.58.22_pnzcqi.png">
</details>

<details>
 <summary>Be sure to have a valid oauth token to be able to make requests to the admin API</summary>
 <img src="https://res.cloudinary.com/dtgdh7noz/image/upload/v1593785530/Bildschirmfoto_2020-07-03_um_16.58.39_df5vpr.png">
</details>

## Re-Send ESD mail for an order
In version `1.2.11` we added the ability to send the ESD mail again from an order.
Just go to your order and scroll a bit down until the **ESD Mail Service** section,
to send the mail again to your customer

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1607271464/ESD%20Docs/Bildschirmfoto_2020-12-06_um_18.10.22_pgl1k4.png)
*send the ESD mail again*

## Business Events
In version `1.2.11` we added the new Shopware Business Events to the plugin.
You will find now two new business events from the ESD plugin.

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1607271463/ESD%20Docs/Bildschirmfoto_2020-12-06_um_18.10.48_fuzsur.png)
*new business events in 1.2.11*

**Make sure your business event is attached to a SalesChannel**

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1607271463/ESD%20Docs/Bildschirmfoto_2020-12-06_um_18.11.02_bkgm2p.png)
*Business events sales channel*
