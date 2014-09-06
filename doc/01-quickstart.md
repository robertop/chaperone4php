#Quickstart#
You have just put the finishing touches on your new website! It's a masterpiece; 
it uses the latest MVC design patterns, routing, templating, and some nice
CSS transitions to boot. Your boss has given you one last little feature 
request before going live: The boss wants you to add a feature that will send
a follow-up E-mail to all of the users on the third day after they made a 
purchase on the site. "Not a problem, this will be a piece of cake" you say to 
yourself.  You fire up your editor and you write the following:


`crontab`

0 0 * * * php /home/user/site/app/console purchase:followups

`crontab`

`php`

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class FollowupCommand extends ContainerAwareCommand 

	protected function execute(InputInterface $input, OutputInterface $output) {
		$followUpTime = strtotime('-3 days');
		
		$purchases = $this->getDoctrine()->getManager()
			->getRepository('Acme:Purchase')
			->findBy(array(
				'createdAt' => date('Y-m-d H:i:s', $followUpTime)
			));
		foreach ($purchases as $purchase) {
			$user = $this->getDoctrine()->getManager()
				->find('Acme:User', $purchase->getUserId());
			$this->sendFollowupEmail($user);	
		}
	}	
}

`php`
