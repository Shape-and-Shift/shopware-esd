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

## Add your digital media to the product
To add the digital media to the product, we will use the [Bulk Edit API](https://shopware.stoplight.io/docs/admin-api/adminapi.json/paths/~1_action~1sync/post) to do it.

First, we need to upload a media file if it doesn't exist in our store, you can take a look at the Admin-API how to add a media file [here](https://shopware.stoplight.io/docs/admin-api/docs/guides/media-handling.md#upload-the-resource-directly).

After finished adding the media you've got the mediaId, we will use it to add to the product through the Bulk Edit API with my example below.

Full **POST** example to the endpoint `/api/_action/syncl`:
```
[
  {
    "key": "write",
    "action": "upsert",
    "entity": "product",
    "payload": [
      {
        "id": "d930fd29db604957bc18f98530e06c47",
        "versionId": "0fa91ce3e96a4bc2be4bd9ce752c3425",
        "esd": {
          "id": "4b3f70ce628949fcb6d4e8a2d295f5cc",
          "esdMedia": [
            {
              "mediaId": "01bb489a85e14f1085cf814c110f5f85"
            }
          ]
        }
      }
    ]
  }
]
```
Explain:
- `payload.id` is productId
- `payload.versionId` is product.versionId
- `esd.id` is the esd id of this product, and if your product doesn't have it, you can generate a new one
- `esd.esdMedia.mediaId` is the media ID you want to add to the product

<details>
 <summary>So a fully working request would look like this example with testing tool</summary>
 <img src="https://res.cloudinary.com/dlp4wd3ng/image/upload/v1630293471/Screenshot_from_2021-08-30_10-16-32_iukiwm.png">
</details>

<details>
  <summary>A practice for creating a product with digital media attached</summary>
  <pre><code>[
  {
    "key": "write",
    "action": "upsert",
    "entity": "product",
    "payload": [
      {
        "id": "d47aa1700fa248e5b147861c54aab3f5",
        "taxId": "c4ccbc056e41461bbd0f07a1f68d7013",
        "featureSetId": "4a6d48155744418e889cdc6ba132df79",
        "price": [
          {
            "currencyId": "b7d2554b0ce847cd82f3ac9bd1c0dfca",
            "net": 84.033613445378,
            "linked": true,
            "gross": 100
          }
        ],
        "productNumber": "SW10000",
        "stock": 100,
        "active": true,
        "purchasePrices": [
          {
            "currencyId": "b7d2554b0ce847cd82f3ac9bd1c0dfca",
            "net": 0,
            "linked": true,
            "gross": 0
          }
        ],
        "name": "esd product",
        "visibilities": [
          {
            "id": "8480748ab62a43e89d674eef687c8bab",
            "productId": "d47aa1700fa248e5b147861c54aab3f5",
            "salesChannelId": "f864c45dcb0c4e8bba36287f9bd29a0a",
            "visibility": 30
          }
        ],
        "esd": {
          "id": "7921470085d740ec88f0daa912a93d70",
          "esdMedia": [
            {
              "mediaId": "01bb489a85e14f1085cf814c110f5f85"
            }
          ]
        }
      }
    ]
  }
]</code></pre>
</details>

## Re-Send ESD mail for an order
In version `1.2.11` we added the ability to send the ESD mail again from an order.
Just go to your order and scroll a bit down until the **ESD Mail Service** section,
to send the mail again to your customer

![](https://res.cloudinary.com/dtgdh7noz/image/upload/v1607271464/ESD%20Docs/Bildschirmfoto_2020-12-06_um_18.10.22_pgl1k4.png)
*send the ESD mail again*
