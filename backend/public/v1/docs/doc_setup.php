<?php
/**
 * @OA\OpenApi(
 *     openapi="3.0.3"
 * )
 * 
 * @OA\Info(
 *     title="AutoParts API",
 *     description="AutoParts API",
 *     version="1.0",
 *     @OA\Contact(
 *         email="nedim.hodzic@stu.ibu.edu.ba",
 *         name="Nedim Hodžić"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost/NedimHodzic/web-programming-project/backend/",
 *     description="API server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="ApiKey",
 *     type="apiKey",
 *     in="header",
 *     name="Authentication"
 * )
 */