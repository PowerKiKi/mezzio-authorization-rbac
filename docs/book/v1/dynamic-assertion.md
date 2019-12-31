# Dynamic Assertion

In some cases you will need to authorize a role based on a specific HTTP request.
For instance, imagine that you have an "editor" role that can add/update/delete
a page in a Content Management System (CMS). We want to prevent an "editor" from
modifying pages they have not created.

These types of authorization are called [dynamic assertions](https://docs.laminas.dev/laminas-permissions-rbac/examples/#dynamic-assertions)
and are implemented via the `Laminas\Permissions\Rbac\AssertionInterface` of
[laminas-permissions-rbac](https://github.com/laminas/laminas-permissions-rbac).

In order to use it, this package provides `LaminasRbacAssertionInterface`,
which extends `Laminas\Permissions\Rbac\AssertionInterface`:

```php
namespace Mezzio\Authorization\Rbac;

use Psr\Http\Message\ServerRequestInterface;
use Laminas\Permissions\Rbac\AssertionInterface;

interface LaminasRbacAssertionInterface extends AssertionInterface
{
    public function setRequest(ServerRequestInterface $request) : void;
}
```

The `Laminas\Permissions\Rbac\AssertionInterface` defines the following:

```php
namespace Laminas\Permissions\Rbac;

interface AssertionInterface
{
    public function assert(Rbac $rbac, RoleInterface $role, string $permission) : bool;
}
```

Going back to our use case, we can build a class to manage the "editor"
authorization requirements, as follows:

```php
use Mezzio\Authorization\Rbac\LaminasRbacAssertionInterface;
use App\Service\Article;

class EditorAuth implements LaminasRbacAssertionInterface
{
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function assert(Rbac $rbac, RoleInterface $role, string $permission)
    {
        $user = $this->request->getAttribute(UserInterface::class, false);
        return $this->article->isUserOwner($user->getIdentity(), $this->request);
    }
}
```

Where `Article` is a class that checks if the identified user is the owner of
the article referenced in the HTTP request.

If you manage articles using a SQL database, the implementation of
`isUserOwner()` might look like the following:

```php
public function isUserOwner(string $identity, ServerRequestInterface $request): bool
{
    // get the article {article_id} attribute specified in the route
    $url = $request->getAttribute('article_id', false);
    if (! $url) {
        return false;
    }
    $sth = $this->pdo->prepare(
        'SELECT * FROM article WHERE url = :url AND owner = :identity'
    );
    $sth->bindParam(':url', $url);
    $sth->bindParam(':identity', $identity);
    if (! $sth->execute()) {
        return false;
    }
    $row = $sth->fetch();
    return ! empty($row);
}
```

To pass the `Article` dependency to your assertion, you can use a Factory class
that generates the `EditorAuth` class instance, as follows:

```php
use App\Service\Article;

class EditorAuthFactory
{
    public function __invoke(ContainerInterface $container) : EditorAuth
    {
        return new EditorAuth(
            $container->get(Article::class)
        );
    }
}
```

And configure the service container to use `EditorAuthFactory` to point to
`EditorAuth`, using the following configuration:

```php
return [    
    'dependencies' => [
        'factories' => [
            // ...
            EditorAuth::class => EditorAuthFactory::class
        ]
    ]
];
```
