<?PHP
class dashboardAction {
	
	function index(){
		$request = new Request;		
		$request->setModel("www/admin/dashboard/viewDashboard.htm");
		return true;
	}
}
?>