# STACK 4.3.7

This is a fork of [maths/moodle-qtype_stack](https://github.com/maths/moodle-qtype_stack) system with support for Dockerized API server.

STACK is an assessment system for mathematics, science and related disciplines.  STACK is a question type for the Moodle learning management system, and also the ILIAS learning management system.

STACK was created by [Chris Sangwin](http://www.maths.ed.ac.uk/~csangwin/) of the University of Edinburgh, and includes the work of many [other contributors](https://github.com/maths/moodle-qtype_stack/blob/master/doc/en/About/Credits.md). A demonstration server is available at the University of Edinburgh:  [https://stack.maths.ed.ac.uk/](https://stack.maths.ed.ac.uk/).

STACK is based on continuing research and use at the University of Edinburgh, the Open University, Aalto, Loughborough University, the University of Birmingham and others.

## Docker usage

This is a fork tailored to run Stack API server as a Docker container in [TIM](https://gitlab.com/tim-jyu/tim) stack.

Please note the following when trying to use or setup your own container

### Build targets

Currently `Dockerfile` contains two targets:

* `prod`: Sets up PHP 7.4, `gnuplot` and `stack` server. Runs `entrypoint_install_and_run.sh` as its entrypoint.
* `dev`: Same as `prod` but in addition starts `sshd`. Runs `entrypoint_install_and_run.sh` as its entrypoint (see `docker-compose` section below for more info).

### Configuration and Maxima

Use `api/config.php.docker` file to edit Stack API config. It currently contains options relevant to TIM.

Currently the fork is pre-configured to run MaximaPool API using [goemaxima server](https://github.com/mathinstitut/goemaxima).
As such, there is no local Maxima installation that you can access within the container.

## Usage examples with `docker-compose`

### Run production API server and Maxima

```yaml
version: "3.7"
services:
  maxima:
    image: timimages/goemaxima:2020113000-latest
  stack:
    build:
      context: .
      target: prod
    depends_on: 
      - maxima
    ports:
      - "49992:80"
    volumes:
      - ./plots:/var/data/api/stack/plots:rw
      - ./plots:/var/www/html/plots:rw
      - ./api/config.php.docker:/var/www/html/config.php:rw
      - ./entrypoint_install_and_run.sh:/var/www/html/entrypoint_install_and_run.sh
```

This will

* Start MaximaPool server (accessible via `http://maxima:8080/maxima/` endpoint)
* Build and start Stack API server in production mode on port 80 and makes it accessible via `localhost:49992`


### Run development API server and Maxima

To run a development server, you can mount `entrypoint_install_and_run_debug.sh` to `entrypoint_install_and_run.sh`.  
This will start an sshd server with the following options

* Username: `root`
* Password: `test`
* Port: `22`

```yaml
version: "3.7"
services:
  maxima:
    image: timimages/goemaxima:2020113000-latest
  stack:
    build:
      context: .
      target: prod
    ports:
      - "49992:80"
    depends_on: 
      - maxima
    volumes:
      - ./plots:/var/data/api/stack/plots:rw
      - ./plots:/var/www/html/plots:rw
      - ./api/config.php.docker:/var/www/html/config.php:rw
      - ./entrypoint_install_and_run_debug.sh:/var/www/html/entrypoint_install_and_run.sh
```

## Syncing changes with upstream

From time to time, this fork needs to be synced with [upstream](https://github.com/maths/moodle-qtype_stack).  
At this moment `api4.3` is the fork on upstream that contains up-to-date API.

To pull changes from upstream to this fork, you can [merge changes from upstream](https://docs.github.com/en/github/collaborating-with-issues-and-pull-requests/merging-an-upstream-repository-into-your-fork):

```bash 
git pull https://github.com/maths/moodle-qtype_stack api4.3
```

This may require resolving merge conflicts.

## Documentation

The [documentation is here](https://stack-assessment.org/), including the installation instructions.

## License

STACK is Licensed under the [GNU General Public, License Version 3](https://github.com/maths/moodle-qtype_stack/blob/master/COPYING.txt).
