
#pre-conditions#
* low disk space
* low memory available
* (DONE) existance / writable directories
* DB connectivity
* network access
* script instances (pid files)
* load average

#post-conditions#
* warn on approach to memory_limit
* warn on low disk space
* warn on script duration

#bulk operations#
* (DONE) bulk loading with low memory consumption
* bulk loading with automatic record paging
* (DONE) bulk saving
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

#calling other processes#
* exec, system calls record exit code