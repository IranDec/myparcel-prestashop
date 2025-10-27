MyParcel module (MVP)

Install: upload the zip in PrestaShop Modules > Upload a module.
Settings: configure API URL, API Key (Bearer) and target state IDs (comma separated) in module configuration.
When an order changes to a configured state, the module sends order data to the API URL and expects JSON with a 'tracking' or 'tracking_number' field. It saves tracking to order_carrier and generates a label HTML file under modules/myparcel/labels/label_{id_order}.html
