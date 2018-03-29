        ___          _     _        _                 
       /   \__ _ ___| |__ | |_ __ _(_)_ __   ___ _ __ 
      / /\ / _` / __| '_ \| __/ _` | | '_ \ / _ \ '__|
     / /_// (_| \__ \ | | | || (_| | | | | |  __/ |   
    /___,' \__,_|___/_| |_|\__\__,_|_|_| |_|\___|_|   
                                                      
                                      by Juan Treminio

#### Instructions

* Run `$ docker-compose up -d --build` in this directory
* Once Traefik is running and ready, change into the `project` 
    directory and again run `$ docker-compose up -d --build`
* You can see Traefik running at http://docker.localhost:8080

#### Information

This configuration includes Traefik, which is required for
communicating with the services in your project.

If you added a webserver service like Nginx or Apache, you do not
need to add an entry into your system's `/etc/hosts` file - Traefik
handles this automatically.

For more information, visit https://docs.traefik.io
