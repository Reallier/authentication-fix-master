/**
 * 实人认证的检查结果
 */
export interface CertCheckResult {
	success: boolean;
	data: CertCheckResultData
}

export interface CertCheckResultData {
	certStatus: boolean;
	message?: string;
}

/**
 * 手机认证的检查结果
 */
export interface PhoneCheckResult {
	success: boolean;
	data: PhoneCheckResultData
}

export interface PhoneCheckResultData {
	phoneStatus: boolean;
	message?: string;
}