import axios from "axios";

async function getAddressFromCoords(lat, lon) {
  const access_key = "d6d23d265100dfca9843436bb5eb847d";
  const url = "https://api.positionstack.com/v1/reverse";

  try {
    const response = await axios.get(url, {
      params: {
        access_key,
        query: `${lat},${lon}`,
        limit: 1
      }
    });

    if (
      response.data &&
      response.data.data &&
      response.data.data.length > 0
    ) {
      const first = response.data.data[0];
      return first.label || null;
    }
    return null;
  } catch (error) {
    console.error("API error:", error);
    return null;
  }
}
