import { Request, Response } from "express";
import { AppError } from "../../utils/AppError";

const VPS_AI_CHAT_URL = process.env.AI_CHAT_URL || "http://13.204.75.195:3000/chat";

export const handleChat = async (req: Request, res: Response) => {
  const { message } = req.body;

  if (!message || typeof message !== "string" || !message.trim()) {
    throw new AppError(400, "Message is required");
  }

  const trimmedMessage = message.trim();

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 25000); // 25s timeout for AI response

    const vpsResponse = await fetch(VPS_AI_CHAT_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        message: trimmedMessage,
      }),
      signal: controller.signal,
    });

    clearTimeout(timeoutId);

    if (!vpsResponse.ok) {
      console.error(`[Chat Proxy] VPS AI server returned error status: ${vpsResponse.status}`);
      throw new AppError(502, "AI server unavailable. Please try again later.");
    }

    const data = (await vpsResponse.json()) as { reply?: string };

    return res.status(200).json({
      success: true,
      reply: data?.reply || "দুঃখিত, কোনো উত্তর পাওয়া যায়নি।",
    });
  } catch (error: any) {
    if (error instanceof AppError) {
      throw error;
    }
    if (error?.name === "AbortError") {
      throw new AppError(504, "AI server request timed out. Please try again.");
    }
    console.error("[Chat Proxy Error]", error);
    throw new AppError(502, "Failed to connect to AI server. Please try again later.");
  }
};
