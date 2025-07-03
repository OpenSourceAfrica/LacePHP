<p align="center">
  <img src="https://raw.githubusercontent.com/OpenSourceAfrica/LacePHP/main/logo.png" alt="LacePHP" width="200">
</p>

<h1 align="center">LacePHP</h1>

<p align="center">
  <em>A delightful, shoe-themed PHP microframework<br>
  “Lace up your APIs faster than ever!”</em>
</p>

---

## What is LacePHP?

LacePHP is an **offline-first**, **API-first** PHP microframework with a playful, shoe-themed vocabulary:

- **Sockliner**: The application kernel
- **Shoepad**: Advanced bootstrap layer
- **ShoeResponder**: Flexible response formatter & error pages
- **Knots**: Middleware (MagicDebugKnots, RateLimitKnots, MetricsKnots, ShoeGateKnots…)
- **SewGet/SewPost**: Fluent route registration
- **ShoeDeploy**: Zero-dependency SSH-based deploy tool
- **ShoeGenie**: AI-powered API scaffolding (premium)
- **ShoeBuddy**: AI pair-programmer (premium)
- **ShoeHttp**: Lightweight cURL wrapper (REST, SOAP, multipart, auth)

Whether you’re building a quick prototype or a production REST API, LacePHP helps you “lace” things together in minutes—no boilerplate, no heavy dependencies.

---

## Installation

LacePHP works equally well offline (using its own PSR-4 autoloader) or with Composer.

```

### Standalone (offline-first)

1. **Clone the repo** into your project root:

   ```bash
   git clone https://github.com/OpenSourceAfrica/LacePHP.git my-shoe
   cd my-shoe
   ```

2. **Initialize config**:

   ```bash
   cp lace.json.example lace.json
   cp env.example .env
   ```

3. **Set your web root** to `public/` and point `index.php` there to:

   ```php
   <?php
   require __DIR__ . '/../boot/solepad.php';
   ```

4. **Enjoy offline**—no Composer, no internet required.

---

## Folder Structure

```
├── public/  
│   └── index.php  
├── lacebox/  
│   ├── Sole/        # Core kernel, router, responder, deploy, AI agents, HTTP client  
│   ├── Tongue/      # Services like GraphQLSchema and Tunnel service  
│   ├── Knots/       # Middleware classes
│   ├── Heel/        # Core framework routes to Dashboard, metrics, docs, health and the rest
│   ├── Insole/      # Core PHP version switching strategy
│   ├── Shoelace/    # Interface and Absrtact classes
│   ├── Strap/       # Guards classes for core authentication types
│   └── …  
├── weave/            # Your app code  
│   ├── Controllers/  
│   ├── Models/  
│   ├── Middlewares/
│   ├── Helpers/
│   ├── Libraries/
│   ├── Services/
│   ├── Validators/
│   ├── Views/  
│   └── Plugins/  
├── shoebox/          # Generated migrations, cache, logs, views, tasks  
├── routes/           # route definitions (api.php, web.php, etc.)  
├── config/           # config/lace.php, config/production.php …  
├── shoedeploy.php    # deploy config  
├── lace.json         # system config & feature flags    
└── .env              # secrets & env overrides
```
---

## Cobble ORM (PHP 7.2+ Compatible)

LacePHP’s lightweight ORM gives you an expressive, object-oriented way to work with your database—fully backward-compatible to PHP 7.2.

### Defining a Model

```php
namespace Weave\Models;

use Lacebox\Sole\Cobble\Model;

class Post extends Model
{
    // Optional: override table name
    // protected static $table = 'blog_posts';

    // Which columns may be mass-assigned
    protected $fillable = ['title', 'body', 'user_id'];

    // Relationships:

    /** Belongs to a User */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Has many Comments */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
```

### Fetching & Saving

```php
// Fetch all posts (array of Post instances)
$allPosts = Post::all();

// Find by primary key
$post = Post::find(1);
$post->title = 'Updated Title';
$post->save();    // only the changed fields are updated

// Create a new post
$new = new Post([
    'title'   => 'Hello World',
    'body'    => 'My first post',
    'user_id' => 2,
], false);
$new->save();     // performs insert, sets $new->id
```

### Eager Loading Relations

```php
// Load post #1 plus its author and comments in one go
$post = Post::find(1)->with(['author', 'comments']);

// Access related data
echo $post->author->name;

foreach ($post->comments as $comment) {
    echo $comment->body;
}
```
### Request

```php
// Use the sole_request helper, it sanitizes your input against attacks
$email = sole_request()->input('email');
````
### Validation

```php
// Be fluent with our powerful request validation and do much more. Define your rules with comma delimiter.
        RequestValidator::getInstance()
            ->setCustomRules([
                'isEven' => new IsEvenRule()
            ])
            ->setRules([
                'email'    => 'required,email',
                'password' => 'required,min:8',
                'age'      => 'required,custom:isEven'
            ])
            ->validate();

        RequestValidator::getInstance()
            ->lace_break()            // per‐field bail
            ->setRules([
                'email'    => 'required,email',
                'password' => 'min[8]',
            ])
            ->validate();             // on fail: sends 422+JSON and exit

````
## Defining Routes

```php
use Lacebox\Sole\Router;

/** Basic route */
$router->sewGet('/hello', [\Weave\Controllers\LaceUpController::class, 'hello']);

/** Grouped routes */
$router->group([
    'prefix'     => '/admin',
    'middleware' => [\Lacebox\Knots\RateLimitKnots::class],
    'namespace'  => 'Admin',
], function($r) {
    $r->sewGet(   '/dashboard', ['DashboardController','index']);
    $r->sewPost(  '/users',     ['UserController','store']);
});
```

---

## Middleware (Knots)

Attach middleware **per-route**, **global**, or via groups:

```php
// Per-route guard:
$router->sewGet('/secure', ['SecuredController','index'], [
    '_guard' => 'token',        // ShoeTokenGuard resolves via strap configuration
    \Lacebox\Knots\SomeOtherKnots::class
]);
```

---

## CLI Helpers

LacePHP ships with a single `lace` binary (in `public/` or project root):

```bash
php lace route:list          # list all routes
php lace route:docs          # generate OpenAPI docs
php lace stitch controller MyController
php lace stitch route MyRoute
php lace stitch model Post
php lace stitch middleware AuthKnots
php lace enable composer     # install Composer deps
php lace dev:watch           # watch routes & reload
php lace app:run             # run the HTTP server
php lace deploy [env]        # ShoeDeploy your code
php lace ai:status           # show AI quota/tier
php lace ai:scaffold "Blog API with posts & comments"
php lace buddy path.php 42 "Why is this null?"  # ShoeBuddy help
php lace timer list          # list scheduled tasks
php lace timer run           # run due tasks (Aglets)
php lace metrics reset       # reset metrics data
```

---

## ShoeDeploy (Zero-Dependency Deployment)

Configure `shoedeploy.php`:

```php
return [
  'default'=>'staging',
  'environments'=>[
    'staging'=>[
      'host'=>'staging.example.com',
      'user'=>'deploy',
      'path'=>'/var/www/myapp',
      'branch'=>'develop',
    ],
    // ...
  ],
  'hooks'=>[
    'beforeDeploy'=>function(){ /* run tests locally */ },
    'afterDeploy'=>function(){ /* warm caches remotely */ },
  ],
];
```

Deploy with:

```bash
php lace deploy staging
```

---

## ShoeGenie & ShoeBuddy (AI-Powered)

- **ShoeGenie** (CRUD scaffolding):  
  ```bash
  php lace ai:scaffold "I need a posts API with title:string, body:text"
  ```

- **ShoeBuddy** (pair-programmer):  
  ```bash
  php lace buddy src/Controller/Foo.php 27 "Why is $this->config empty here?"
  ```

>  Premium tier only. Caching, rate-limits, and quotas enforced automatically.

---

## ShoeHttp (Lightweight HTTP Client)

```php
use Lacebox\Sole\Http\ShoeHttp;

// JSON POST
$res = (new ShoeHttp())
    ->url('https://api.example.com/items')
    ->method('POST')
    ->authBearer($token)
    ->json(['name'=>'Shoe','size'=>42])
    ->send();

echo $res['status'], $res['body'];
```

---

## Scheduling & Tasks (Aglets)

```php
// in shoebox/Kernel.php
schedule()->task('Cleanup', '*/10 * * * *', 'Weave\Tasks\Cleaner@run');
schedule()->task('Report', '0 6 * * *',      'Weave\Tasks\Reporter@daily');
```

Run scheduled tasks:

```bash
php lace timer list
php lace timer run
```

---

## Contributing

1. Fork the repo  
2. Create a feature branch  
3. Run tests and lint:  
   ```bash
   composer test
   ```  
4. Submit a PR—happy to review!

---

## License

LacePHP is open-source software licensed under the **MIT License**.  
&copy; 2025 Akinyele Olubodun — [akinyeleolubodun.com](https://www.akinyeleolubodun.com) | [blog.akinyeleolubodun.com](https://blog.akinyeleolubodun.com)
