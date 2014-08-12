
#pre-conditions#
* low disk space
* low memory available
* existance / writable directories
* DB connectivity
* network access
* script instances (pid files)

#post-conditions#
* warn on approach to memory_limit
* warn on low disk space
* warn on script duration


#bulk operations#
* bulk loading with low memory consumption
* bulk loading with automatic record paging
* bulk saving
* bulk HTTP calls (curl-multi)

#logging#
* emergency: send email (throttled)
* warning: log into file, send reminder notice on a daily/weekly basis
* debug: for devs only
* interactive: when run manually, see progress bars

#scheduled scripts#
* retry with decaying stategy: retry in 1 minute, then 5 minutes, then 10 ...


#continous scripts#
* signal handling: safely handle process termination by completing
  transactions
* easy integration with rabbit mq / gearman