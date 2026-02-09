<?php
$pageTitle = "SPG Portal — ხედვა";
require __DIR__ . "/inc/bootstrap.php";
require __DIR__ . "/inc/people_section.php";
include __DIR__ . "/header.php";

// Add people like:
// $people = [
//   ['image' => 'assets/team/person.jpg', 'name' => 'Name Lastname', 'position' => 'Position'],
// ];
$people = [];
include __DIR__ . "/header.php";
?>
<section class="section">
  <div class="container" style="max-width:1000px;padding:40px 0">
    <h2 style="margin-bottom:12px">ხედვა • ღირებულება</h2>
    <p style="white-space:pre-line;line-height:1.9">
ორგანიზაციის ღირებულებებია:

ინკლუზიურობა- ჩვენ ვქმნით მრავალფეროვან და ტოლერანტულ გარემოს, სადაც ყველა ხმა თანაბრად ფასობს.

გაძლიერება- ახალგაზრდებს ვაძლევთ  ცოდნის გაღრმავებასისა და პრაქტიკული უნარების გამომუშავების შესაძლებლობას. 

კოლაბორაცია – ჩვენ გვჯერა, რომ გუნდური მუშაობა და პარტნიორობა წარმატების მიღწევის უალტერნატივო გზაა

სტრუქტურა

 


    </p>
    <div style="margin-top:16px">
      <img src="<?=h(url('e42c4f55fc66b25ec25290da10201f08.jpg'))?>" alt="ღირებულებები" style="width:100%;display:block;border-radius:18px">
    </div>
  </div>
</section>
<?php render_people_section('Team', $people); ?>
<?php include __DIR__ . "/footer.php"; ?>
