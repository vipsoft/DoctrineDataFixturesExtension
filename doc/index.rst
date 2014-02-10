=============================
DoctrineDataFixturesExtension
=============================

The DoctrineDataFixtures extension increases feature test isolation by reloading ORM data fixtures between features.

Installation
============
This extension requires:

* Behat 2.4+
* Mink 1.4+
* Doctrine ORM 2.x
* [Symfony2Extension](http://extensions.behat.org/symfony2/)

Through Composer
----------------
1. Set dependencies in your **composer.json**:

.. code-block:: js

    {
        "require": {
            ...
            "vipsoft/doctrine-data-fixtures-extension": "*"
        }
    }

2. Install/update your vendors:

.. code-block:: bash

    $ curl http://getcomposer.org/installer | php
    $ php composer.phar install

Through PHAR
------------
Download the .phar archive:

* `doctrine_data_fixtures_extension.phar <http://behat.org/downloads/doctrine_data_fixtures_extension.phar>`_

Configuration
=============
Activate extension in your **behat.yml** and define any fixtures to be loaded:

.. code-block:: yaml

    # behat.yml
    default:
      # ...
      extensions:
        VIPSoft\DoctrineDataFixturesExtension\Extension:
          lifetime:    feature
          autoload:    true
          directories: ~
          fixtures:    ~

When **lifetime** is set to "feature" (or unspecified), data fixtures are reloaded between feature files.  Alternately,
when **lifetime** is set to "scenario", data fixtures are reloaded between scenarios (i.e., increased
test isolation at the expense of increased run time).

When **autoload** is true, the DoctrineDataFixtures extension will load the data fixtures for all
registered bundles (similar to ``app/console doctrine:fixtures:load``).

When **fixtures** is set and **autoload** is false, the DoctrineDataFixtures
extension will load the specified fixture classes.

When **directories** is set and **autoload** is false, the DoctrineDataFixtures
extension will load the data fixtures globbed from the respective directories.

.. code-block:: yaml

    # behat.yml
    default:
      # ...
      extensions:
        VIPSoft\DoctrineDataFixturesExtension\Extension:
          lifetime: feature
          autoload: true
          directories:
            - /project/src/AcmeAnalytics/Tests/DataFixtures/ORM
          fixtures:
            - Acme\StoreBundle\DataFixture\ORM\Categories
            - Acme\StoreBundle\DataFixture\ORM\Apps
            - Acme\VendorBundle\DataFixture\ORM\Vendors

Limitations
-----------
When using the SqlLiteDriver, the .db file is cached to speed up reloading.  You should periodically clear the cache as it does not detect changes to the data fixture contents because the hash is based on the collection of data fixture class names.

Source
======
`Github <https://github.com/vipsoft/DoctrineDataFixturesExtension>`_

Copyright
=========
Copyright (c) 2012 Anthon Pang.  See **LICENSE** for details.

Contributors
============
* Anthon Pang `(robocoder) <http://github.com/robocoder>`_
* `Others <https://github.com/vipsoft/DoctrineDataFixturesExtension/graphs/contributors>`_
