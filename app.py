from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
from sklearn.linear_model import LinearRegression
from pydantic import BaseModel

app = FastAPI()
from fastapi import FastAPI

app = FastAPI()

@app.get("/")
def read_root():
    return {"message": "Hello, FastAPI is working!"}

# Enable CORS for frontend requests
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allow all origins (Frontend & Backend communication)
    allow_methods=["*"],
    allow_headers=["*"],
)

# Load dataset
file_path = "new.csv"
df = pd.read_csv(file_path)

df.columns = df.columns.str.replace("_x0020_", "_")  # Fix column names if needed
df.dropna(inplace=True)
df['Arrival_Date'] = pd.to_datetime(df['Arrival_Date'])
df.sort_values(by=['Arrival_Date'], inplace=True)

# Convert categorical variables to numerical
label_encoders = {}
categorical_cols = ['State', 'District', 'Market', 'Commodity', 'Variety', 'Grade']

for col in categorical_cols:
    le = LabelEncoder()
    df[col] = le.fit_transform(df[col])
    label_encoders[col] = le  # Save encoder for future use

df['Day_Number'] = (df['Arrival_Date'] - df['Arrival_Date'].min()).dt.days

X = df[['State', 'District', 'Market', 'Commodity', 'Variety', 'Grade', 'Day_Number', 'Min_Price', 'Max_Price']]
y = df['Modal_Price']

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# Train Linear Regression model
lr_model = LinearRegression()
lr_model.fit(X_train, y_train)

# Define input model for API
class CommodityRequest(BaseModel):
    commodity: str

@app.post("/predict")
async def predict(data: CommodityRequest):
    user_commodity = data.commodity.title()
    
    if user_commodity not in label_encoders['Commodity'].classes_:
        return {"error": "Commodity not found. Please check spelling."}
    
    encoded_commodity = label_encoders['Commodity'].transform([user_commodity])[0]
    commodity_data = df[df['Commodity'] == encoded_commodity]
    
    if commodity_data.empty:
        return {"error": "No data available for this commodity."}
    
    latest_entry = commodity_data.iloc[-1].copy()
    current_price = latest_entry['Modal_Price']
    
    future_days = [0, 3, 7, 15]
    forecast_labels = ["Today", "In 3 days", "Next week", "Next 15 days"]
    forecasted_prices = {}

    for days, label in zip(future_days, forecast_labels):
        latest_entry['Day_Number'] += days
        future_price = lr_model.predict([latest_entry[X.columns]])[0]
        forecasted_prices[label] = future_price

    return {"current_price": current_price, "forecast": forecasted_prices}

# Run FastAPI with: uvicorn app:app --reload
