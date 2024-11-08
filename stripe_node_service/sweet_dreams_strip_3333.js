require('dotenv').config(); // Ensure you install dotenv and load environment variables

const express = require('express');
const Stripe = require('stripe');
const bodyParser = require('body-parser');
const app = express();

// Initialize Stripe with your secret key from environment variables
const stripe = Stripe(process.env.STRIPE_PUBLIC_KEY);

// Use bodyParser middleware to parse JSON data
app.use(bodyParser.json());

// POST endpoint to add a card
// app.post('/card-tokenize', async (req, res) => {
//     try {
//         const token = await stripe.tokens.create({
//             card: {
//                 number: req.body.card_number,
//                 exp_month: req.body.exp_month,
//                 exp_year: req.body.exp_year,
//                 cvc: req.body.cvc,
//                 name: req.body.name
//             },
//         });
//         res.status(200).json({
//             status: 1,
//             msg: 'Tokenized successfully',
//             data: token
//         });
//     } catch (err) {
//         res.status(400).json({ // Use appropriate HTTP status code
//             status: 0,
//             data: err,
//             msg: req.body
//         });
//     }
// });
app.post('/card-tokenize', async (req, res) => {
    try {
        // Extract card details from `cardDetails` in the request body
        const cardDetails = req.body.cardDetails;

        // Create a Stripe token using the card details
        const token = await stripe.tokens.create({
            card: {
                number: cardDetails.number,
                exp_month: cardDetails.exp_month,
                exp_year: cardDetails.exp_year,
                cvc: cardDetails.cvc,
                name: cardDetails.name // Optional, if included in the request
            },
        });

        // Send success response
        res.status(200).json({
            status: 1,
            msg: 'Tokenized successfully',
            data: token
        });
    } catch (err) {
        // Send error response
        res.status(400).json({
            status: 0,
            data: err,
            msg: 'Error tokenizing card'
        });
    }
});

// Run server on port 3000
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
