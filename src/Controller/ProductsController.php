<?php
namespace App\Controller;

use App\Controller\AppController;
// use Cake\Utility\Inflector;
use Cake\Utility\Hash;

class ProductsController extends AppController
{

////////////////////////////////////////////////////////////////////////////////

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Cart');
    }

////////////////////////////////////////////////////////////////////////////////

    public function sitemap()
    {
        $this->loadModel('Items');
        $items = $this->Items->find('all', [
            'conditions' => [
                'Items.active' => 1,
            ],
            'fields' => [
                'Items.slug'
            ],
            'conditions' => [
                'Items.active' => 1,
            ]
        ]);
        $this->set(compact('items'));

        $this->loadModel('Projects');
        $projects = $this->Projects->find('all', [
            'conditions' => [
                'Projects.active' => 1,
            ],
            'fields' => [
                'Projects.slug'
            ],
        ])->all();
        $this->set(compact('projects'));

        $products = $this->Products->find('all', [
            'order' => [
                'Products.name' => 'ASC'
            ],
            'fields' => [
                'Products.slug'
            ],
            'conditions' => [
                'Products.active' => 1,
            ]
        ]);
        $this->set(compact('products'));

        $categories = $this->Products->Categories->find('all', [
            'order' => [
                'Categories.sort' => 'ASC',
                'Categories.name' => 'ASC',
            ],
            'fields' => [
                'Categories.slug'
            ],
            'conditions' => [
                'Categories.active' => 1,
            ]
        ]);
        $this->set(compact('categories'));

        $this->response->type('xml');
        $this->viewBuilder()->layout(false);

    }

////////////////////////////////////////////////////////////////////////////////

    public function index()
    {
        $this->paginate = [
            'contain' => ['Categories'],
            'order' => [
                'Products.name' => 'ASC',
            ],
            'conditions' => [
                'Products.active' => 1,
            ],
            'limit' => 12
        ];
        $products = $this->paginate($this->Products);
        $this->set(compact('products'));
    }

////////////////////////////////////////////////////////////////////////////////

    public function view($slug = null)
    {
        $categories = $this->Products->Categories->find('all', [
            'order' => [
                'Categories.sort' => 'ASC',
                'Categories.name' => 'ASC',
            ]
        ]);
        $this->set(compact('categories'));

        $product = $this->Products->find('all', [
            'contain' => ['Categories'],
            'conditions' => [
                'Products.slug' => $slug,
                'Products.active' => 1,
            ]
        ])->first();
        if(empty($product)) {
            return $this->redirect(['action' => 'index']);
        }

        $productoptions = $this->Products->Productoptions->find('all', [
            'fields' => [
                'id',
                'name',
                'price',
                'weight',
            ],
            'conditions' => [
                'Productoptions.product_id' => $product->id,
                'Productoptions.name NOT LIKE' => '%Please Select%',
            ],
            'order' => [
                'Productoptions.name' => 'ASC',
            ],
        ])->all();

        $productoptionlists = [];
        foreach($productoptions as $productoption):
            $price = sprintf('%01.2f', $productoption->price);
            $productoption->newprice = (float) $price;
            $productoptionlists[$productoption->id] = $productoption->name . ' - ' . '$' . $price;
        endforeach;

        $weights = Hash::extract($productoptions->toArray(), '{n}.weight');
        $weights = array_unique($weights);
        natcasesort($weights);

        $shorts = Hash::extract($productoptions->toArray(), '{n}.short');
        $shorts = array_unique($shorts);
        natcasesort($shorts);

        $this->set(compact('shorts', 'weights'));

        $attribute = null;
        if(isset($productption->attribute_id)) {
            $attribute = $this->Products->Productoptions->Attributes->find('all', [
                'conditions' => [
                    'Attributes.id' => $productption->attribute_id,
                ],
            ])->first();
        }

        $this->set('product', $product);
        $this->set('productoptions', $productoptions);
        $this->set('productoptionlists', $productoptionlists);
        $this->set('attribute', $attribute);
        $this->set('_serialize', ['product']);
    }

////////////////////////////////////////////////////////////////////////////////

    public function add()
    {
        if ($this->request->is('post')) {

            $id = $this->request->data['id'];
            $quantity = 1;
            $productoptionId = isset($this->request->data['productoptionlist']) ? $this->request->data['productoptionlist'] : 0;

            $product = $this->Products->get($id, [
                'contain' => []
            ]);
            if(empty($product)) {
                $this->Flash->error('Invalid request');
            } else {
                $this->Cart->add($id, $quantity, $productoptionId);
                $this->Flash->success($product->name . ' has been added to the shopping cart');
            }

            return $this->redirect($this->referer());
        } else {
            return $this->redirect(['action' => 'index']);
        }
    }

////////////////////////////////////////////////////////////////////////////////

    public function remove($id = null) {
        $product = $this->Cart->remove($id);
        if(!empty($product)) {
            // $this->Flash->error($product['name'] . ' was removed from your shopping cart');
        }
        return $this->redirect(['action' => 'cart']);
    }

////////////////////////////////////////////////////////////////////////////////

    public function cart()
    {
        $shop = $this->Cart->getcart();
        $this->set(compact('shop'));
    }

////////////////////////////////////////////////////////////////////////////////

    public function cartupdate() {
        if ($this->request->is('post')) {
            foreach($this->request->data as $key => $value) {
                $a = explode('-', $key);
                $b = explode('_', $a[1]);
                $this->Cart->add($b[0], $value, $b[1]);
                $this->Cart->cart();
            }
        }
        return $this->redirect(['action' => 'cart']);
    }

////////////////////////////////////////////////////////////////////////////////

    public function itemupdate() {
        if ($this->request->is('ajax')) {
            $id = $this->request->data['id'];
            $quantity = isset($this->request->data['quantity']) ? $this->request->data['quantity'] : 1;
            if(isset($this->request->data['mods']) && ($this->request->data['mods'] > 0)) {
                $productmodId = $this->request->data['mods'];
            } else {
                $productmodId = 0;
            }
            $product = $this->Cart->add($id, $quantity, $productmodId);
        }
        $cart = $this->Cart->getcart();
        echo json_encode($cart);
        die;
    }

////////////////////////////////////////////////////////////////////////////////

    public function clear()
    {
        $this->Cart->clear();
        $this->Flash->success('The shopping cart is cleared');
        return $this->redirect(['action' => 'index']);
    }

////////////////////////////////////////////////////////////////////////////////

}
