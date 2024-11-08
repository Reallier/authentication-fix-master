/**
 * 实人认证的返回结果
 */
export interface QrCodeResult {
	success: boolean;
	data: QrCodeResultData
}

export interface QrCodeResultData {
	code?: string;
	message?: string;
}

/**
 * 手机验证码的返回结果
 */
export interface VerificationCodeResult {
	success: boolean;
	data: VerificationCodeCodeResultData

}

export interface VerificationCodeCodeResultData {
	message?: string;
	remain?: number;
}