<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Row Calculation</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .remove-btn {
            color: red;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <form id="bookingForm" action="{{ route('bookings.store') }}" method="POST">
        @csrf
        <table id="bookingTable">
            <thead>
                <tr>
                    <th>Bed</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Person</th>
                    <th>Price Per Person</th>
                    <th>Total Row Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="booking-row">
                    <td><input type="number" name="bed[]" class="bed" required></td>
                    <td><input type="date" name="from_date[]" class="from-date" required></td>
                    <td><input type="date" name="to_date[]" class="to-date" required></td>
                    <td><input type="number" name="person[]" class="person" required></td>
                    <td><input type="number" name="price_per_person[]" class="price-per-person" required></td>
                    <td><input type="number" name="total_price[]" class="total-price" readonly></td>
                    <td><span class="remove-btn">Remove</span></td>
                </tr>
            </tbody>
        </table>
        <button type="button" id="addRowBtn">Add Row</button>
        <p>Total Price: <span id="grandTotalPrice">0</span></p>
        <button type="submit">Submit</button>
    </form>

    <script>
        $(document).ready(function() {
            function calculateRowPrice(row) {
                let bed = parseInt(row.find('.bed').val()) || 0;
                let person = parseInt(row.find('.person').val()) || 0;
                let pricePerPerson = parseInt(row.find('.price-per-person').val()) || 0;

                let fromDate = new Date(row.find('.from-date').val());
                let toDate = new Date(row.find('.to-date').val());
                let daysDifference = Math.ceil((toDate - fromDate) / (1000 * 60 * 60 * 24)) || 1;

                // Calculate total price per row
                let totalPrice = bed * daysDifference * pricePerPerson * person;
                row.find('.total-price').val(totalPrice);

                // Update grand total price
                calculateGrandTotalPrice();
            }

            function calculateGrandTotalPrice() {
                let grandTotalPrice = 0;
                $('.total-price').each(function() {
                    grandTotalPrice += parseInt($(this).val()) || 0;
                });
                $('#grandTotalPrice').text(grandTotalPrice);
            }

            // Add new row
            $('#addRowBtn').click(function() {
                let newRow = `
                <tr class="booking-row">
                    <td><input type="number" name="bed[]" class="bed" required></td>
                    <td><input type="date" name="from_date[]" class="from-date" required></td>
                    <td><input type="date" name="to_date[]" class="to-date" required></td>
                    <td><input type="number" name="person[]" class="person" required></td>
                    <td><input type="number" name="price_per_person[]" class="price-per-person" required></td>
                    <td><input type="number" name="total_price[]" class="total-price" readonly></td>
                    <td><span class="remove-btn">Remove</span></td>
                </tr>`;
                
                $('#bookingTable tbody').append(newRow);
            });

            // Handle input change events
            $(document).on('input', '.bed, .person, .price-per-person, .from-date, .to-date', function() {
                let row = $(this).closest('tr');
                calculateRowPrice(row);
            });

            // Remove row and update total price
            $(document).on('click', '.remove-btn', function() {
                $(this).closest('tr').remove();
                calculateGrandTotalPrice();
            });
        });
    </script>
</body>
</html>
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = ['user_id', 'bed', 'from_date', 'to_date', 'person', 'price_per_person', 'total_price'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSummary extends Model
{
    protected $fillable = ['user_id', 'grand_total'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
public function up()
{
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('bed');
        $table->date('from_date');
        $table->date('to_date');
        $table->integer('person');
        $table->decimal('price_per_person', 8, 2);
        $table->decimal('total_price', 8, 2);
        $table->timestamps();
    });
}
public function up()
{
    Schema::create('booking_summaries', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->decimal('grand_total', 8, 2);
        $table->timestamps();
    });
}
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingSummary;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $userId = Auth::id();  // Get the logged-in user ID
        $totalPrice = 0;

        // Save each booking row to the `bookings` table
        foreach ($request->bed as $index => $bed) {
            $fromDate = $request->from_date[$index];
            $toDate = $request->to_date[$index];
            $person = $request->person[$index];
            $pricePerPerson = $request->price_per_person[$index];
            $totalRowPrice = $request->total_price[$index];

            // Insert into bookings table
            Booking::create([
                'user_id' => $userId,
                'bed' => $bed,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'person' => $person,
                'price_per_person' => $pricePerPerson,
                'total_price' => $totalRowPrice,
            ]);

            $totalPrice += $totalRowPrice;
        }

        // Save grand total to `booking_summaries` table
        BookingSummary::create([
            'user_id' => $userId,
            'grand_total' => $totalPrice,
        ]);

        return redirect()->back()->with('success', 'Booking saved successfully!');
    }
}
use App\Http\Controllers\BookingController;

Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
